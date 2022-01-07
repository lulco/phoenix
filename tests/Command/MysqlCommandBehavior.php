<?php

declare(strict_types=1);

namespace Phoenix\Tests\Command;

use Phoenix\Tests\Helpers\Adapter\MysqlCleanupAdapter;
use Phoenix\Tests\Helpers\Pdo\MysqlPdo;

trait MysqlCommandBehavior
{
    protected function getEnvironment(): string
    {
        return 'mysql';
    }

    protected function getAdapter(): MysqlCleanupAdapter
    {
        $pdo = new MysqlPdo();
        return new MysqlCleanupAdapter($pdo);
    }
}
