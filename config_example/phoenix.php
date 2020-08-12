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
        ],
        'pgsql' => [
            'adapter' => 'pgsql',
            'host' => 'localhost',
            'username' => 'postgres',
            'password' => '123',
            'db_name' => 'phoenix',
            'charset' => 'utf8',
        ],
        'custom_connection' => [
            'adapter' => 'mysql', // or pgsql
            'connection' => new PDO('mysql:host=127.0.0.1;port=3306;dbname=mysql;charset=utf8', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT // we not recommend to use silent error mode but for testing purpose that is ok
            ])
        ]
    ],
];
