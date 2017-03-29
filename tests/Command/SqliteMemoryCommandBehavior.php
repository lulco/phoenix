<?php

namespace Phoenix\Tests\Command;

use Phoenix\Tests\Helpers\Adapter\SqliteCleanupAdapter;
use Phoenix\Tests\Helpers\Pdo\SqliteMemoryPdo;

trait SqliteMemoryCommandBehavior
{
    protected function getEnvironment()
    {
        return 'sqlite_memory';
    }

    protected function getAdapter()
    {
        $pdo = new SqliteMemoryPdo();
        return new SqliteCleanupAdapter($pdo);
    }
}
