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
        $dsn = $this->configuration['adapter'] . ':dbname=' . $this->configuration['db_name'] . ';host=' . $this->configuration['host'];
        if (isset($this->configuration['port'])) {
            $dsn .= ';port=' . $this->configuration['port'];
        }
        if (isset($this->configuration['charset'])) {
            if ($this->configuration['adapter'] == 'pgsql') {
                $dsn .= ';options=\'--client_encoding=' . $this->configuration['charset'] . '\'';
            } else {
                $dsn .= ';charset=' . $this->configuration['charset'];
            }
        }
        return $dsn;
    }
    
    public function getUsername()
    {
        return isset($this->configuration['username']) ? $this->configuration['username'] : null;
    }
    
    public function getPassword()
    {
        return isset($this->configuration['password']) ? $this->configuration['password'] : null;
    }
    
    public function getCharset()
    {
        return isset($this->configuration['charset']) ? $this->configuration['charset'] : 'utf8';
    }
}
