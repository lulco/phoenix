<?php

namespace Phoenix\Config;

use PDO;
use Phoenix\Exception\ConfigException;

class EnvironmentConfig
{
    private $configuration;

    public function __construct(array $configuration)
    {
        $this->configuration = $configuration;
    }

    public function getConfiguration(): array
    {
        return $this->configuration;
    }

    public function getAdapter(): string
    {
        return $this->configuration['adapter'];
    }

    public function getDsn(): string
    {
        if ($this->checkConfigValue('dsn')) {
            return $this->configuration['dsn'];
        }
        $dsn = $this->configuration['adapter'] . ':dbname=' . $this->configuration['db_name'] . ';host=' . $this->configuration['host'];
        if ($this->checkConfigValue('port')) {
            $dsn .= ';port=' . $this->configuration['port'];
        }
        if ($this->checkConfigValue('charset')) {
            if ($this->configuration['adapter'] === 'pgsql') {
                $dsn .= ';options=\'--client_encoding=' . $this->configuration['charset'] . '\'';
            } else {
                $dsn .= ';charset=' . $this->configuration['charset'];
            }
        }
        return $dsn;
    }

    public function getUsername(): ?string
    {
        return $this->configuration['username'] ?? null;
    }

    public function getPassword(): ?string
    {
        return $this->configuration['password'] ?? null;
    }

    public function getCharset(): string
    {
        return $this->configuration['charset'] ?? 'utf8';
    }

    public function getVersion(): ?string
    {
        return $this->configuration['version'] ?? null;
    }

    /**
     * If user wish to reuse existing connection, connection can be passed as config parameter 'connection'
     * and will be accessed from here. Otherwise new PDO connection will be created.
     *
     * @return PDO
     * 
     * @throws ConfigException
     */
    public function getConnection(): PDO
    {
        if ($this->checkConfigValue('connection')) {
            if ($this->configuration['connection'] instanceof PDO) {
                return $this->configuration['connection'];
            }
            throw new ConfigException('Connection instance must extend PDO class');
        }
        return new PDO($this->getDsn(), $this->getUsername(), $this->getPassword());
    }

    private function checkConfigValue(string $key): bool
    {
        return isset($this->configuration[$key]) && $this->configuration[$key];
    }
}
