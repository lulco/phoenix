<?php

namespace Phoenix\Database\Adapter;

use PDO;
use Phoenix\Config\EnvironmentConfig;
use Phoenix\Exception\InvalidArgumentValueException;

class AdapterFactory
{
    public static function instance(EnvironmentConfig $config): AdapterInterface
    {
        $pdo = new PDO($config->getDsn(), $config->getUsername(), $config->getPassword());
        if ($config->getAdapter() == 'mysql') {
            $adapter = new MysqlAdapter($pdo);
        } elseif ($config->getAdapter() == 'pgsql') {
            $adapter = new PgsqlAdapter($pdo);
        } else {
            throw new InvalidArgumentValueException('Unknown adapter "' . $config->getAdapter() . '". Use one of value: "mysql", "pgsql".');
        }
        $adapter->setCharset($config->getCharset());
        return $adapter;
    }
}
