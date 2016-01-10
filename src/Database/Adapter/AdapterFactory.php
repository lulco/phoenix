<?php

namespace Phoenix\Database\Adapter;

use PDO;
use Phoenix\Config\EnvironmentConfig;

class AdapterFactory
{
    public static function instance(EnvironmentConfig $config)
    {
        $pdo = new PDO($config->getDsn(), $config->getUsername(), $config->getPassword());
        switch ($config->getAdapter()) {
            case 'mysql':
                return new MysqlAdapter($pdo);
            case 'sqlite':
                return new SqliteAdapter($pdo);
        }
    }
}
