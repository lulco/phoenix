<?php

namespace Phoenix\Tests;

use PDO;
use Phoenix\Config\Config;
use Phoenix\Database\Adapter\SqliteAdapter;
use Phoenix\Migration\Manager;
use PHPUnit_Framework_TestCase;

class ManagerTest extends PHPUnit_Framework_TestCase
{
    public function testUp()
    {
        $config = new Config([
            'migration_dirs' => [
                __DIR__ . '/../../src/Migration/Init/',
                __DIR__ . '/../fake/structure/migration_directory_1/',
            ],
            'environments' => [
                'sqlite' => [
                    'adapter' => 'sqlite',
                    'dsn' => 'sqlite::memory:'
                ],
            ]
        ]);
        $environmentConfig = $config->getEnvironmentConfig('sqlite');
        $pdo = new PDO($environmentConfig->getDsn());
        $adapter = new SqliteAdapter($pdo);
        $manager = new Manager($config, $adapter);
    }
}
