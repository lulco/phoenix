<?php

declare(strict_types=1);

namespace Phoenix\Tests\Helpers\Adapter;

use PDO;
use Phoenix\Database\Adapter\MysqlAdapter;

final class MysqlCleanupAdapter implements CleanupInterface
{
    private MysqlAdapter $mysqlAdapter;

    public function __construct(PDO $pdo)
    {
        $this->mysqlAdapter = new MysqlAdapter($pdo);
    }

    public function cleanupDatabase(): void
    {
        $database = getenv('PHOENIX_MYSQL_DATABASE');
        $this->mysqlAdapter->query(sprintf('DROP DATABASE IF EXISTS `%s`', $database));
        $this->mysqlAdapter->query(sprintf('CREATE DATABASE `%s`', $database));
    }
}
