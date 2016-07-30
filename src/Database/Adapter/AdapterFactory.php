<?php

namespace Phoenix\Database\Adapter;

use PDO;
use Phoenix\Config\EnvironmentConfig;
use Phoenix\Exception\InvalidArgumentValueException;

class AdapterFactory
{
    public static function instance(EnvironmentConfig $config)
    {
        $pdo = new PDO($config->getDsn(), $config->getUsername(), $config->getPassword());
        if ($config->getAdapter() == 'mysql') {
            return new MysqlAdapter($pdo);
        }
        if ($config->getAdapter() == 'pgsql') {
            return new PgsqlAdapter($pdo);
        }
        if ($config->getAdapter() == 'sqlite') {
            return new SqliteAdapter($pdo);
        }
        throw new InvalidArgumentValueException('Unknown adapter "' . $config->getAdapter() . '". Use one of value: "mysql", "pgsql", "sqlite".');
    }
}
