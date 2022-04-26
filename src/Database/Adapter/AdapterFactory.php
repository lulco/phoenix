<?php

declare(strict_types=1);

namespace Phoenix\Database\Adapter;

use PDO;
use Phoenix\Config\EnvironmentConfig;
use Phoenix\Exception\InvalidArgumentValueException;

final class AdapterFactory
{
    public static function instance(EnvironmentConfig $config): AdapterInterface
    {
        $pdo = new PDO($config->getDsn(), $config->getUsername(), $config->getPassword(), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT
        ]);

        sscanf($config->getVersion() ?? $pdo->getAttribute(PDO::ATTR_SERVER_VERSION), '%d.%d.%d', $v1, $v2, $v3);
        $version = implode('.', array_filter([$v1, $v2, $v3], function ($v) {
            return $v !== null;
        }));

        if ($config->getAdapter() === 'mysql') {
            $adapter = new MysqlAdapter($pdo, $version);
        } elseif ($config->getAdapter() === 'pgsql') {
            $adapter = new PgsqlAdapter($pdo, $version);
        } else {
            throw new InvalidArgumentValueException('Unknown adapter "' . $config->getAdapter() . '". Use one of value: "mysql", "pgsql".');
        }
        $adapter->setCharset($config->getCharset());
        $adapter->setCollation($config->getCollation());
        return $adapter;
    }
}
