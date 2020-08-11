<?php

return [
    'migration_dirs' => [
        'phoenix' => __DIR__ . '/../testing_migrations/phoenix',
    ],
    'environments' => [
        'mysql' => [
            'adapter' => 'mysql',
            'host' => 'localhost',
            'port' => '3306', // optional
            'username' => 'root',
            'password' => '123',
            'db_name' => 'phoenix',
            'charset' => 'utf8', // optional
            'connection' => null, // existing PDO instance that you need to be reused; use this or other parameters
        ],
        'pgsql' => [
            'adapter' => 'pgsql',
            'host' => 'localhost',
            'username' => 'postgres',
            'password' => '123',
            'db_name' => 'phoenix',
            'charset' => 'utf8',
            'connection' => null, // existing PDO instance that you need to be reused; use this or other parameters
        ],
    ],
];
