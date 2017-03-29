<?php

namespace Phoenix\Tests\Helpers\Adapter;

use Phoenix\Database\Adapter\MysqlAdapter;

class MysqlCleanupAdapter extends MysqlAdapter implements CleanupInterface
{
    public function cleanupDatabase()
    {
        $database = getenv('PHOENIX_MYSQL_DATABASE');
        $this->execute(sprintf('DROP DATABASE IF EXISTS `%s`', $database));
        $this->execute(sprintf('CREATE DATABASE `%s`', $database));
    }
}
