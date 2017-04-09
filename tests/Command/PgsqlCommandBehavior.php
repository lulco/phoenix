<?php

namespace Phoenix\Tests\Command;

use Phoenix\Tests\Helpers\Adapter\PgsqlCleanupAdapter;
use Phoenix\Tests\Helpers\Pdo\PgsqlPdo;

trait PgsqlCommandBehavior
{
    protected function getEnvironment()
    {
        return 'pgsql';
    }

    protected function getAdapter()
    {
        $pdo = new PgsqlPdo();
        return new PgsqlCleanupAdapter($pdo);
    }
}
