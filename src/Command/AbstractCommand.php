<?php

namespace Phoenix\Command;

use Phoenix\Command\AbstractCommand;
use Phoenix\Command\InitCommand;
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

    /** @var type */
    protected $start;

    /**
     * output data used for json output format
     * @var array
     */
    protected $outputData = [];

    /**
     * @param string $name
     * @return AbstractCommand
     */
    public function setName($name)
    {
        if (!$this->getName()) {
            return parent::setName($name);
        }
        return $this;
    }

    protected function configure()
    {
        $this->addOption('environment', 'e', InputOption::VALUE_REQUIRED, 'Environment');
        $this->addOption('config', 'c', InputOption::VALUE_OPTIONAL, 'Path to config file');
        $this->addOption('config_type', 't', InputOption::VALUE_OPTIONAL, 'Type of config, available values: php, yml, neon, json');
        $this->addOption('output-format', 'f', InputOption::VALUE_REQUIRED, 'Format of the output. Available values: default, json', 'default');
    }

    /**
     * @param array $configuration
     * @return AbstractCommand
     */
    public function setConfig(array $configuration)
    {
        $this->config = new Config($configuration);
        return $this;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    final protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;

        $this->loadConfig($input);

        $environment = $input->getOption('environment') ?: $this->config->getDefaultEnvironment();
        $this->adapter = AdapterFactory::instance($this->config->getEnvironmentConfig($environment));

        $this->manager = new Manager($this->config, $this->adapter);
        $this->check($input, $output);

        $this->start = microtime(true);
        $this->runCommand($input, $output);
        $this->finishCommand($input, $output);
    }

    protected function writeln($message, $options = 0)
    {
        $specialOptions = $this->input->getOption('output-format') === 'json' ? -1 : $options;
        $this->output->writeln($message, $specialOptions);
    }

    private function finishCommand(InputInterface $input, OutputInterface $output)
    {
        $executionTime = microtime(true) - $this->start;
        if ($input->getOption('output-format') === 'json') {
            $this->outputData['execution_time'] = $executionTime;
            $output->write(json_encode($this->outputData));
            return;
        }
        $output->writeln('');
        $output->write('<comment>All done. Took ' . sprintf('%.4fs', $executionTime) . '</comment>');
        $output->writeln('');
    }

    private function loadConfig(InputInterface $input)
    {
        if ($this->config) {
            return;
        }

        $configFile = $input->getOption('config');
        if (!$configFile) {
            $configFile = $this->getDefaultConfig();
        }

        if (!$configFile) {
            throw new ConfigException('No configuration file exists. Create phoenix.php or phoenix.yml or phoenix.neon or phoenix.json in your project root or specify path to your existing config file with --config option');
        }

        if ($configFile && !file_exists($configFile)) {
            throw new ConfigException('Configuration file "' . $configFile . '" doesn\'t exist.');
        }

        $type = $input->getOption('config_type') ?: pathinfo($configFile, PATHINFO_EXTENSION);
        $configParser = ConfigParserFactory::instance($type);
        $configuration = $configParser->parse($configFile);
        $this->config = new Config($configuration);
    }

    private function getDefaultConfig()
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

    private function check(InputInterface $input, OutputInterface $output)
    {
        try {
            $executedMigrations = $this->manager->executedMigrations();
        } catch (DatabaseQueryExecuteException $e) {
            $executedMigrations = false;
            if (!($this instanceof InitCommand)) {
                $init = new InitCommand();
                $init->setConfig($this->config->getConfiguration());
                $init->execute($input, $output);
            }
        }

        if ($executedMigrations !== false && $this instanceof InitCommand) {
            throw new WrongCommandException('Phoenix was already initialized, run migrate or rollback command now.');
        }
    }

    abstract protected function runCommand(InputInterface $input, OutputInterface $output);
}
