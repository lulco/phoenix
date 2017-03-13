<?php

namespace Phoenix\Tests\Command;

use Phoenix\Tests\Helpers\Adapter\SqliteCleanupAdapter;
use Phoenix\Tests\Helpers\Pdo\SqliteMemoryPdo;

trait SqliteCommandBehavior
{
    protected function getEnvironment()
    {
        return 'sqlite';
    }

    protected function getAdapter()
    {
        $pdo = new SqliteMemoryPdo();
        return new SqliteCleanupAdapter($pdo);
    }
}
