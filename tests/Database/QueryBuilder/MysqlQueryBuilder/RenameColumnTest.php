<?php

declare(strict_types=1);

namespace Phoenix\Tests\Database\QueryBuilder\MysqlQueryBuilder;

use Phoenix\Database\Adapter\MysqlAdapter;
use Phoenix\Database\Element\MigrationTable;
use Phoenix\Database\QueryBuilder\MysqlQueryBuilder;
use Phoenix\Tests\Helpers\Adapter\MysqlCleanupAdapter;
use Phoenix\Tests\Helpers\Pdo\MysqlPdo;
use PHPUnit\Framework\TestCase;

final class RenameColumnTest extends TestCase
{
    private MysqlAdapter $adapter;

    protected function setUp(): void
    {
        $pdo = new MysqlPdo();
        $adapter = new MysqlCleanupAdapter($pdo);
        $adapter->cleanupDatabase();

        $pdo = new MysqlPdo(getenv('PHOENIX_MYSQL_DATABASE'));
        $this->adapter = new MysqlAdapter($pdo);
    }

    public function testSimpleRenameColumn(): void
    {
        $queryBuilder = new MysqlQueryBuilder($this->adapter);
        $table = new MigrationTable('test_table');
        $table->addColumn('title', 'string')
            ->addColumn('asdf', 'string')
            ->create();

        foreach ($queryBuilder->createTable($table) as $query) {
            $this->adapter->query($query);
        }

        $table = new MigrationTable('test_table');
        $table->renameColumn('asdf', 'alias');

        $expectedQueries = [
            'ALTER TABLE `test_table` CHANGE COLUMN `asdf` `alias` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;',
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->alterTable($table));
    }
}
