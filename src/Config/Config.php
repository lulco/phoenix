<?php

namespace Phoenix\Config;

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
    }
    
    public function getMigrationDirs()
    {
        return $this->configuration['migration_dirs'];
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
