<?php

declare(strict_types=1);

namespace Phoenix\Tests\Helpers\Adapter;

use Phoenix\Database\Adapter\MysqlAdapter;

final class MysqlCleanupAdapter extends MysqlAdapter implements CleanupInterface
{
    public function cleanupDatabase(): void
    {
        $database = getenv('PHOENIX_MYSQL_DATABASE');
        $this->query(sprintf('DROP DATABASE IF EXISTS `%s`', $database));
        $this->query(sprintf('CREATE DATABASE `%s`', $database));
    }
}
