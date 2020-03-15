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

        // TODO $config->getVersion() - force the version default null and will be taken from server
        sscanf($pdo->getAttribute(PDO::ATTR_SERVER_VERSION), '%d.%d.%d', $v1, $v2, $v3);
//        var_dump($v1, $v2, $v3);
//        var_dump($pdo->getAttribute(PDO::ATTR_SERVER_VERSION));

        if ($config->getAdapter() === 'mysql') {
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
