<?php

namespace Phoenix\Config;

class EnvironmentConfig
{
    private $configuration;
    
    public function __construct(array $configuration)
    {
        $this->configuration = $configuration;
    }
    
    public function getAdapter()
    {
        return $this->configuration['adapter'];
    }
    
    public function getDsn()
    {
        if (isset($this->configuration['dsn'])) {
            return $this->configuration['dsn'];
        }
        return $this->configuration['adapter'] . ':dbname=' . $this->configuration['db_name'] . ';host=' . $this->configuration['host'];
    }
    
    public function getUsername()
    {
        return isset($this->configuration['username']) ? $this->configuration['username'] : null;
    }
    
    public function getPassword()
    {
        return isset($this->configuration['password']) ? $this->configuration['password'] : null;
    }
}
