<?php

namespace Phoenix\Tests\Database\QueryBuilder\MysqlQueryBuilder;

use Phoenix\Database\Adapter\MysqlAdapter;
use Phoenix\Database\Element\MigrationTable;
use Phoenix\Database\QueryBuilder\MysqlQueryBuilder;
use Phoenix\Tests\Helpers\Adapter\MysqlCleanupAdapter;
use Phoenix\Tests\Helpers\Pdo\MysqlPdo;
use PHPUnit\Framework\TestCase;

class CopyTableTest extends TestCase
{
    private $adapter;

    protected function setUp()
    {
        $pdo = new MysqlPdo();
        $adapter = new MysqlCleanupAdapter($pdo);
        $adapter->cleanupDatabase();

        $pdo = new MysqlPdo(getenv('PHOENIX_MYSQL_DATABASE'));
        $this->adapter = new MysqlAdapter($pdo);
    }

    public function testCopyDefault()
    {
        $table = new MigrationTable('copy_default');
        $table->copy('new_copy_default');

        $queryBuilder = new MysqlQueryBuilder($this->adapter);
        $expectedQueries = [
            'CREATE TABLE `new_copy_default` LIKE `copy_default`;',
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->copyTable($table));
    }

    public function testCopyOnlyStructure()
    {
        $table = new MigrationTable('copy_only_structure');
        $table->copy('new_copy_only_structure', MigrationTable::COPY_ONLY_STRUCTURE);

        $queryBuilder = new MysqlQueryBuilder($this->adapter);
        $expectedQueries = [
            'CREATE TABLE `new_copy_only_structure` LIKE `copy_only_structure`;',
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->copyTable($table));
    }

    public function testCopyOnlyData()
    {
        $queryBuilder = new MysqlQueryBuilder($this->adapter);
        $table = new MigrationTable('copy_only_data');
        $table->addColumn('title', 'string')
            ->create();

        foreach ($queryBuilder->createTable($table) as $query) {
            $this->adapter->query($query);
        }

        $table = new MigrationTable('copy_only_data');
        $table->copy('new_copy_only_data', MigrationTable::COPY_ONLY_DATA);

        $expectedQueries = [
            'INSERT INTO `new_copy_only_data` (id,title) SELECT id,title FROM `copy_only_data`;',
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->copyTable($table));
    }

    public function testCopyStructureAndData()
    {
        $queryBuilder = new MysqlQueryBuilder($this->adapter);
        $table = new MigrationTable('copy_structure_and_data');
        $table->addColumn('title', 'string')
            ->addColumn('bodytext', 'text')
            ->create();

        foreach ($queryBuilder->createTable($table) as $query) {
            $this->adapter->query($query);
        }

        $table = new MigrationTable('copy_structure_and_data');
        $table->copy('new_copy_structure_and_data', MigrationTable::COPY_STRUCTURE_AND_DATA);

        $expectedQueries = [
            'CREATE TABLE `new_copy_structure_and_data` LIKE `copy_structure_and_data`;',
            'INSERT INTO `new_copy_structure_and_data` (id,title,bodytext) SELECT id,title,bodytext FROM `copy_structure_and_data`;',
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->copyTable($table));
    }
}
