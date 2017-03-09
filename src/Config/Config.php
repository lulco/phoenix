<?php

namespace Phoenix\Config;

use Phoenix\Exception\ConfigException;
use Phoenix\Exception\InvalidArgumentValueException;

class Config
{
    private $configuration = [
        'log_table_name' => 'phoenix_log',
        'migration_dirs' => [],
        'environments' => [],
        'default_environment' => '',
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

    public function getConfiguration()
    {
        return $this->configuration;
    }

    public function getMigrationDirs()
    {
        return $this->configuration['migration_dirs'];
    }

    public function getMigrationDir($dir = null)
    {
        if ($dir === null) {
            if (count($this->configuration['migration_dirs']) > 1) {
                throw new InvalidArgumentValueException('There are more then 1 migration dirs. Use one of them: ' . implode(', ', array_keys($this->configuration['migration_dirs'])));
            }
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
