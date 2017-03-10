<?php

return [
    'migration_dirs' => [
        'phoenix' => __DIR__ . '/../testing_migrations/phoenix',
    ],
    'environments' => [
        'sqlite' => [
            'adapter' => 'sqlite',
            'dsn' => 'sqlite::memory:',
        ],
        'mysql' => [
            'adapter' => 'mysql',
            'host' => 'localhost',
            'username' => 'root',
            'password' => '123',
            'db_name' => 'phoenix',
            'charset' => 'utf8',
        ],
    ],
];
