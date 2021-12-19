<?php

declare(strict_types=1);

namespace Phoenix\Tests\Helpers\Adapter;

use PDO;
use Phoenix\Database\Adapter\PgsqlAdapter;

final class PgsqlCleanupAdapter implements CleanupInterface
{
    private PgsqlAdapter $pgsqlAdapter;

    public function __construct(PDO $pdo)
    {
        $this->pgsqlAdapter = new PgsqlAdapter($pdo);
    }

    public function cleanupDatabase(): void
    {
        $database = getenv('PHOENIX_PGSQL_DATABASE');
        $this->pgsqlAdapter->query(sprintf("SELECT pg_terminate_backend (pg_stat_activity.pid) FROM pg_stat_activity WHERE pg_stat_activity.datname = '%s'", $database));
        $this->pgsqlAdapter->query(sprintf('DROP DATABASE IF EXISTS %s', $database));
        $this->pgsqlAdapter->query(sprintf("SELECT pg_terminate_backend (pg_stat_activity.pid) FROM pg_stat_activity WHERE pg_stat_activity.datname = '%s'", $database));
        $this->pgsqlAdapter->query(sprintf('CREATE DATABASE %s', $database));
    }
}
