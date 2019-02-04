<?php

namespace Phoenix\Command;

use Phoenix\Config\Config;
use Phoenix\Config\Parser\ConfigParserFactory;
use Phoenix\Database\Adapter\AdapterFactory;
use Phoenix\Database\Adapter\AdapterInterface;
use Phoenix\Exception\ConfigException;
use Phoenix\Exception\DatabaseQueryExecuteException;
use Phoenix\Exception\WrongCommandException;
use Phoenix\Migration\Manager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractCommand extends Command
{
    /** @var Config */
    protected $config = null;

    /** @var AdapterInterface */
    protected $adapter;

    /** @var Manager */
    protected $manager;

    /** @var InputInterface */
    protected $input;

    /** @var OutputInterface */
    protected $output;

    /** @var double */
    protected $start;

    /**
     * output data used for json output format
     * @var array
     */
    protected $outputData = [];

    public function setName($name): self
    {
        if (!$this->getName()) {
            return parent::setName($name);
        }
        return $this;
    }

    protected function configure(): void
    {
        $this->addOption('environment', 'e', InputOption::VALUE_REQUIRED, 'Environment');
        $this->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'Path to config file');
        $this->addOption('config_type', 't', InputOption::VALUE_REQUIRED, 'Type of config, available values: php, yml, neon, json');
        $this->addOption('output-format', 'f', InputOption::VALUE_REQUIRED, 'Format of the output. Available values: default, json', 'default');
    }

    public function setConfig(array $configuration): AbstractCommand
    {
        $this->config = new Config($configuration);
        return $this;
    }

    final public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $this->output = $output;
        $this->config = $this->loadConfig();

        $environment = $this->input->getOption('environment') ?: $this->config->getDefaultEnvironment();
        $this->adapter = AdapterFactory::instance($this->config->getEnvironmentConfig($environment));

        $this->manager = new Manager($this->config, $this->adapter);
        $this->check();

        $this->start = microtime(true);
        $this->runCommand();
        $this->finishCommand();
        return 0;
    }

    /**
     * @param string|iterable $message
     */
    protected function writeln($message, int $options = 0): void
    {
        $this->output->writeln($message, $this->isDefaultOutput() ? $options : -1);
    }

    protected function isDefaultOutput(): bool
    {
        return $this->input->getOption('output-format') === null || $this->input->getOption('output-format') === 'default';
    }

    private function finishCommand(): void
    {
        $executionTime = microtime(true) - $this->start;
        if ($this->input->getOption('output-format') === 'json') {
            $this->outputData['execution_time'] = $executionTime;
            $this->output->write(json_encode($this->outputData));
            return;
        }
        $this->output->writeln('');
        $this->output->write('<comment>All done. Took ' . sprintf('%.4fs', $executionTime) . '</comment>');
        $this->output->writeln('');
    }

    private function loadConfig(): Config
    {
        if ($this->config !== null) {
            return $this->config;
        }

        $configFile = $this->input->getOption('config');
        if (!$configFile) {
            $configFile = $this->getDefaultConfig();
        }

        if (!$configFile) {
            throw new ConfigException('No configuration file exists. Create phoenix.php or phoenix.yml or phoenix.neon or phoenix.json in your project root or specify path to your existing config file with --config option');
        }
        return $this->parseConfig($configFile);
    }

    private function getDefaultConfig(): ?string
    {
        $defaultConfigFiles = [
            'phoenix.php',
            'phoenix.yml',
            'phoenix.neon',
            'phoenix.json',
        ];
        foreach ($defaultConfigFiles as $defaultConfigFile) {
            if (file_exists($defaultConfigFile)) {
                return $defaultConfigFile;
            }
        }
        return null;
    }

    private function parseConfig(string $configFile): Config
    {
        if ($configFile && !file_exists($configFile)) {
            throw new ConfigException('Configuration file "' . $configFile . '" doesn\'t exist.');
        }

        $type = $this->input->getOption('config_type') ?: pathinfo($configFile, PATHINFO_EXTENSION);
        $configParser = ConfigParserFactory::instance($type);
        $configuration = $configParser->parse($configFile);
        return new Config($configuration);
    }

    private function check(): void
    {
        try {
            $executedMigrations = $this->manager->executedMigrations();
        } catch (DatabaseQueryExecuteException $e) {
            $executedMigrations = false;
            if (!$this instanceof InitCommand) {
                $init = new InitCommand();
                $init->setConfig($this->config->getConfiguration());
                $verbosity = $this->output->getVerbosity();
                $this->output->setVerbosity(OutputInterface::VERBOSITY_QUIET);
                $init->execute($this->input, $this->output);
                $this->output->setVerbosity($verbosity);
            }
        }

        if ($executedMigrations !== false && $this instanceof InitCommand) {
            throw new WrongCommandException('Phoenix was already initialized, run migrate or rollback command now.');
        }
    }

    abstract protected function runCommand(): void;
}
