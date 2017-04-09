<?php

namespace Phoenix\Tests\Helpers\Adapter;

use Phoenix\Database\Adapter\PgsqlAdapter;

class PgsqlCleanupAdapter extends PgsqlAdapter implements CleanupInterface
{
    public function cleanupDatabase()
    {
        $database = getenv('PHOENIX_PGSQL_DATABASE');
        $this->execute(sprintf("SELECT pg_terminate_backend (pg_stat_activity.pid) FROM pg_stat_activity WHERE pg_stat_activity.datname = '%s'", $database));
        $this->execute(sprintf('DROP DATABASE IF EXISTS %s', $database));
        $this->execute(sprintf("SELECT pg_terminate_backend (pg_stat_activity.pid) FROM pg_stat_activity WHERE pg_stat_activity.datname = '%s'", $database));
        $this->execute(sprintf('CREATE DATABASE %s', $database));
    }
}
