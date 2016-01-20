<?php

namespace Phoenix\Config;

use Phoenix\Exception\ConfigException;
use Phoenix\Exception\InvalidArgumentValueException;

class Config
{
    private $configuration = [
        'migration_dirs' => [],
        'default_environment' => '',
        'log_table_name' => 'phoenix_log',
        'environments' => [],
    ];
    
    public function __construct(array $configuration)
    {
        $this->configuration = array_merge($this->configuration, $configuration);
        if (empty($this->configuration['migration_dirs'])) {
            throw new ConfigException('Empty migration dirs');
        }
        
        if (empty($this->configuration['environments'])) {
            throw new ConfigException('Empty environments');
        }
    }
    
    public function getMigrationDirs()
    {
        return $this->configuration['migration_dirs'];
    }
    
    public function getMigrationDir($dir = null)
    {
        if ($dir === null) {
            return current($this->configuration['migration_dirs']);
        }
        
        if (isset($this->configuration['migration_dirs'][$dir])) {
            return $this->configuration['migration_dirs'][$dir];
        }
        
        throw new InvalidArgumentValueException('Directory "' . $dir . '" doesn\'t exist. Use: ' . implode(', ', array_keys($this->configuration['migration_dirs'])));
    }
    
    public function getLogTableName()
    {
        return $this->configuration['log_table_name'];
    }
    
    public function getDefaultEnvironment()
    {
        if ($this->configuration['default_environment']) {
            return $this->configuration['default_environment'];
        }
        return current(array_keys($this->configuration['environments']));
    }
    
    public function getEnvironmentConfig($environment)
    {
        return new EnvironmentConfig($this->configuration['environments'][$environment]);
    }
}
