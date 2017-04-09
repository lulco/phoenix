<?php

namespace Phoenix\Tests\Command;

use Phoenix\Tests\Helpers\Adapter\SqliteCleanupAdapter;
use Phoenix\Tests\Helpers\Pdo\SqlitePdo;

trait SqliteCommandBehavior
{
    protected function getEnvironment()
    {
        return 'sqlite';
    }

    protected function getAdapter()
    {
        $pdo = new SqlitePdo();
        return new SqliteCleanupAdapter($pdo);
    }
}
