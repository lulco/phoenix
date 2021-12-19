<?php

declare(strict_types=1);

namespace Database\QueryBuilder\MysqlWithJsonQueryBuilder;

use Phoenix\Database\Adapter\MysqlAdapter;
use Phoenix\Database\Element\MigrationTable;
use Phoenix\Database\QueryBuilder\MysqlWithJsonQueryBuilder;
use Phoenix\Tests\Helpers\Adapter\MysqlCleanupAdapter;
use Phoenix\Tests\Helpers\Pdo\MysqlPdo;
use PHPUnit\Framework\TestCase;

final class CreateTableTest extends TestCase
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

    public function testSimpleCreate(): void
    {
        $table = new MigrationTable('simple');
        $table->addPrimary(true);
        $table->setCharset('utf8');
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('title', 'string'));
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('settings', 'json'));

        $queryBuilder = new MysqlWithJsonQueryBuilder($this->adapter);
        $expectedQueries = [
            'CREATE TABLE `simple` (`id` int(11) NOT NULL AUTO_INCREMENT,`title` varchar(255) NOT NULL,`settings` json NOT NULL,PRIMARY KEY (`id`)) DEFAULT CHARACTER SET=utf8;'
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->createTable($table));
    }
}
