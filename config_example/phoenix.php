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
            'connection' => new PDO('mysql://'), // or any instance that extends PDO and compatible with selected adapter
        ]
    ],
];
