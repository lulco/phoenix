<?php

namespace Phoenix\Tests\Helpers\Adapter;

use Phoenix\Database\Adapter\SqliteAdapter;

class SqliteCleanupAdapter extends SqliteAdapter implements CleanupInterface
{
    public function cleanupDatabase()
    {
        file_put_contents(__DIR__ . '/../../../testing_migrations/phoenix.sqlite', '');
    }
}
