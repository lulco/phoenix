<?php

declare(strict_types=1);

namespace Phoenix\Tests\Helpers\Adapter;

use Phoenix\Database\Adapter\PgsqlAdapter;

final class PgsqlCleanupAdapter extends PgsqlAdapter implements CleanupInterface
{
    public function cleanupDatabase(): void
    {
        $database = getenv('PHOENIX_PGSQL_DATABASE');
        $this->query(sprintf("SELECT pg_terminate_backend (pg_stat_activity.pid) FROM pg_stat_activity WHERE pg_stat_activity.datname = '%s'", $database));
        $this->query(sprintf('DROP DATABASE IF EXISTS %s', $database));
        $this->query(sprintf("SELECT pg_terminate_backend (pg_stat_activity.pid) FROM pg_stat_activity WHERE pg_stat_activity.datname = '%s'", $database));
        $this->query(sprintf('CREATE DATABASE %s', $database));
    }
}
