<?php

namespace Phoenix\Tests\Command;

use Phoenix\Tests\Helpers\Adapter\MysqlCleanupAdapter;
use Phoenix\Tests\Helpers\Pdo\MysqlPdo;

trait MysqlCommandBehavior
{
    protected function getEnvironment()
    {
        return 'mysql';
    }

    protected function getAdapter()
    {
        $pdo = new MysqlPdo();
        return new MysqlCleanupAdapter($pdo);
    }
}
