<?php

use Ramsey\Uuid\UuidFactory;
use Ramsey\Uuid\UuidFactoryInterface;

return [
    'migration_dirs' => [
        'phoenix' => __DIR__ . '/../phoenix',
    ],
    'environments' => [
        'mysql' => [
            'adapter' => 'mysql',
            'host' => getenv('PHOENIX_MYSQL_HOST'),
            'port' => getenv('PHOENIX_MYSQL_PORT'),
            'username' => getenv('PHOENIX_MYSQL_USERNAME'),
            'password' => getenv('PHOENIX_MYSQL_PASSWORD'),
            'db_name' => getenv('PHOENIX_MYSQL_DATABASE'),
            'charset' => getenv('PHOENIX_MYSQL_CHARSET'),
        ],
        'pgsql' => [
            'adapter' => 'pgsql',
            'host' => getenv('PHOENIX_PGSQL_HOST'),
            'port' => getenv('PHOENIX_PGSQL_PORT'),
            'username' => getenv('PHOENIX_PGSQL_USERNAME'),
            'password' => getenv('PHOENIX_PGSQL_PASSWORD'),
            'db_name' => getenv('PHOENIX_PGSQL_DATABASE'),
            'charset' => getenv('PHOENIX_PGSQL_CHARSET'),
        ],
    ],
    'dependencies' => [
        UuidFactoryInterface::class => new UuidFactory(),
    ],
];
