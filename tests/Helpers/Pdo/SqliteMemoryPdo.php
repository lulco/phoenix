<?php

namespace Phoenix\Tests\Helpers\Pdo;

use PDO;

class SqliteMemoryPdo extends PDO
{
    public function __construct()
    {
        $dsn = 'sqlite:' . __DIR__ . '/../../../testing_migrations/phoenix.sqlite';
        parent::__construct($dsn);
    }
}
