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
        'pgsql' => [
            'adapter' => 'pgsql',
            'db_name' => 'phoenix',
            'host' => 'localhost',
            'username' => 'postgres',
            'password' => '123',
            'charset' => 'utf8',
        ],
        'sqlite' => [
            'adapter' => 'sqlite',
            'dsn' => 'sqlite:' . __DIR__ . '/phoenix.sqlite',
        ],
    ],
];

foreach (array_keys($configuration['environments']) as $environment) {
    echo "Adapter: $environment\n";
    $config = new Config($configuration);
    $adapter = AdapterFactory::instance($config->getEnvironmentConfig($environment));

    $initMigration = new Init($adapter, $config->getLogTableName());
    $initMigration->migrate();

    do {
        $adapter = AdapterFactory::instance($config->getEnvironmentConfig($environment));
        $manager = new Manager($config, $adapter);
        $migrations = $manager->findMigrationsToExecute(Manager::TYPE_UP, Manager::TARGET_FIRST);
        foreach ($migrations as $migration) {
            $migration->migrate();
            $manager->logExecution($migration);
            $migration->rollback();
            $manager->removeExecution($migration);
            $migration->migrate();
            $manager->logExecution($migration);
    //        print_R($migration->getExecutedQueries());
        }
    } while ($migrations);
    echo "All OK\n\n";
}
