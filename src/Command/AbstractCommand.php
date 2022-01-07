<?php

declare(strict_types=1);

namespace Phoenix\Command;

use InvalidArgumentException;
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
    protected ?Config $config = null;

    protected AdapterInterface $adapter;

    protected Manager $manager;

    protected InputInterface $input;

    protected OutputInterface $output;

    protected float $start;

    /**
     * output data used for json output format
     * @var array<string, mixed>
     */
    protected array $outputData = [];

    protected function configure(): void
    {
        $this->addOption('environment', 'e', InputOption::VALUE_REQUIRED, 'Environment');
        $this->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'Path to config file');
        $this->addOption('config_type', 't', InputOption::VALUE_REQUIRED, 'Type of config, available values: php, yml, neon, json');
        $this->addOption('output-format', 'f', InputOption::VALUE_REQUIRED, 'Format of the output. Available values: default, json', 'default');
    }

    /**
     * @param array<string, mixed> $configuration
     * @return self
     * @throws ConfigException
     */
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

        /** @var string $environment */
        $environment = $this->input->getOption('environment') ?: $this->getConfig()->getDefaultEnvironment();
        $environmentConfig = $this->getConfig()->getEnvironmentConfig($environment);
        if (!$environmentConfig) {
            throw new InvalidArgumentException('Environment ' . $environment . ' doesn\'t exist');
        }
        $this->adapter = AdapterFactory::instance($environmentConfig);

        $this->manager = new Manager($this->getConfig(), $this->adapter);
        $this->check();

        $this->start = microtime(true);
        $this->runCommand();
        $this->finishCommand();
        return 0;
    }

    /**
     * @param string[] $messages
     */
    protected function writeln(array $messages, int $options = 0): void
    {
        foreach ($messages as $message) {
            $this->output->writeln($message, $this->isDefaultOutput() ? $options : -1);
        }
    }

    protected function isDefaultOutput(): bool
    {
        return $this->input->getOption('output-format') === null || $this->input->getOption('output-format') === 'default';
    }

    protected function getConfig(): Config
    {
        if ($this->config === null) {
            throw new ConfigException('Config is not set');
        }
        return $this->config;
    }

    private function finishCommand(): void
    {
        $executionTime = microtime(true) - $this->start;
        if ($this->input->getOption('output-format') === 'json') {
            $this->outputData['execution_time'] = $executionTime;
            $this->output->write((string)json_encode($this->outputData));
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

        /** @var string|null $configFile */
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

        /** @var string $type */
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
                $init->setConfig($this->getConfig()->getConfiguration());
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
