<?php

use Phoenix\Config\Config;
use Phoenix\Database\Adapter\AdapterFactory;
use Phoenix\Migration\Init\Init;
use Phoenix\Migration\Manager;

require_once __DIR__ . '/../vendor/autoload.php';

$configuration = [
    'migration_dirs' => [
        __DIR__ . '/phoenix',
    ],
    'environments' => [
        'mysql' => [
            'adapter' => 'mysql',
            'db_name' => 'phoenix',
            'host' => 'localhost',
            'username' => 'root',
            'password' => '123',
            'charset' => 'utf8',
        ],
        'sqlite' => [
            'adapter' => 'sqlite',
            'dsn' => 'sqlite:' . __DIR__ . '/phoenix.sqlite',
        ],
        'pgsql' => [
            'adapter' => 'pgsql',
            'db_name' => 'phoenix',
            'host' => 'localhost',
            'username' => 'postgres',
            'password' => '123',
            'charset' => 'utf8',
        ],
    ],
];

foreach (array_keys($configuration['environments']) as $environment) {
    echo "Adapter: $environment\n";
    $config = new Config($configuration);
    $adapter = AdapterFactory::instance($config->getEnvironmentConfig($environment));

    $initMigration = new Init($adapter, $config->getLogTableName());
    $initMigration->migrate();

    $manager = new Manager($config, $adapter);
    $migrations = $manager->findMigrationsToExecute();
    foreach ($migrations as $migration) {
        $migration->migrate();
        $manager->logExecution($migration);
        $migration->rollback();
        $manager->removeExecution($migration);
        $migration->migrate();
        $manager->logExecution($migration);
//        print_R($migration->getExecutedQueries());
    }
    echo "All OK\n\n";
}
