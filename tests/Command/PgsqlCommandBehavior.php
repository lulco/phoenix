<?php

declare(strict_types=1);

namespace Phoenix\Tests\Command;

use Phoenix\Tests\Helpers\Adapter\PgsqlCleanupAdapter;
use Phoenix\Tests\Helpers\Pdo\PgsqlPdo;

trait PgsqlCommandBehavior
{
    protected function getEnvironment(): string
    {
        return 'pgsql';
    }

    protected function getAdapter(): PgsqlCleanupAdapter
    {
        $pdo = new PgsqlPdo();
        return new PgsqlCleanupAdapter($pdo);
    }
}
