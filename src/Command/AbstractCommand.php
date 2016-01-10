<?php

namespace Phoenix\Command;

use PDO;
use Phoenix\Config\Config;
use Phoenix\Database\Adapter\AdapterFactory;
use Phoenix\Database\Adapter\AdapterInterface;
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
        $this->addOption('environment', 'e', InputOption::VALUE_REQUIRED);
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
        if ($this->config === null) {
            $configuration = require __DIR__ . '/../../bin/config.php'; // TODO vymysliet ako sem dostat config array ked to bude napr. neon, yaml, php a ine
            $this->config = new Config($configuration);
        }
        
        $environment = $input->getOption('environment') ?: $this->config->getDefaultEnvironment();
        $this->adapter = AdapterFactory::instance($this->config->getEnvironmentConfig($environment));
        
        $this->manager = new Manager($this->config, $this->adapter);
        $this->check();
        
        $this->runCommand($input, $output);
    }
    
    private function check()
    {
        try {
            $executedMigrations = $this->manager->executedMigrations();
        } catch (DatabaseQueryExecuteException $e) {
            $executedMigrations = false;
            if (!($this instanceof InitCommand)) {
                throw new WrongCommandException('Phoenix is not initialized, run init command first.');
            }
        }
        
        if ($executedMigrations !== false && $this instanceof InitCommand) {
            throw new WrongCommandException('Phoenix was already initialized, run migrate or rollback command now.');
        }
    }
    
    abstract protected function runCommand(InputInterface $input, OutputInterface $output);
}
