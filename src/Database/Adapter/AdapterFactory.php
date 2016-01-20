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
        switch ($config->getAdapter()) {
            case 'mysql':
                return new MysqlAdapter($pdo);
            case 'pgsql':
                return new PgsqlAdapter($pdo);
            case 'sqlite':
                return new SqliteAdapter($pdo);
            default: throw new InvalidArgumentValueException('Unknown adapter "' . $config->getAdapter() . '". Use one of value: "mysql", "sqlite".');
        }
    }
}
