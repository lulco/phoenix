<?php

return [
    'migration_dirs' => [
        'phoenix' => __DIR__ . '/../testing_migrations/phoenix',
    ],
    'environments' => [
        'mysql' => [
            'adapter' => 'mysql',
            'host' => 'localhost',
            'username' => 'root',
            'password' => '123',
            'db_name' => 'libs',
            'charset' => 'utf8',
        ],
        'sqlite' => [
            'adapter' => 'sqlite',
            'dsn' => 'sqlite::memory:',
        ],
    ],
];
