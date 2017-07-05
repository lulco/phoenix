<?php

namespace Phoenix\Tests\Database\QueryBuilder\MysqlQueryBuilder;

use Phoenix\Database\Element\MigrationTable;
use Phoenix\Database\QueryBuilder\MysqlQueryBuilder;
use PHPUnit_Framework_TestCase;

class CopyTableTest extends PHPUnit_Framework_TestCase
{
    public function testCopyDefault()
    {
        $table = new MigrationTable('copy_default');
        $table->copy('new_copy_default');

        $queryBuilder = new MysqlQueryBuilder();
        $expectedQueries = [
            'CREATE TABLE `new_copy_default` LIKE `copy_default`;',
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->copyTable($table));
    }

    public function testCopyOnlyStructure()
    {
        $table = new MigrationTable('copy_only_structure');
        $table->copy('new_copy_only_structure', MigrationTable::COPY_ONLY_STRUCTURE);

        $queryBuilder = new MysqlQueryBuilder();
        $expectedQueries = [
            'CREATE TABLE `new_copy_only_structure` LIKE `copy_only_structure`;',
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->copyTable($table));
    }

    public function testCopyOnlyData()
    {
        $table = new MigrationTable('copy_only_data');
        $table->copy('new_copy_only_data', MigrationTable::COPY_ONLY_DATA);

        $queryBuilder = new MysqlQueryBuilder();
        $expectedQueries = [
            'INSERT INTO `new_copy_only_data` SELECT * FROM `copy_only_data`;',
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->copyTable($table));
    }

    public function testCopyStructureAndData()
    {
        $table = new MigrationTable('copy_structure_and_data');
        $table->copy('new_copy_structure_and_data', MigrationTable::COPY_STRUCTURE_AND_DATA);

        $queryBuilder = new MysqlQueryBuilder();
        $expectedQueries = [
            'CREATE TABLE `new_copy_structure_and_data` LIKE `copy_structure_and_data`;',
            'INSERT INTO `new_copy_structure_and_data` SELECT * FROM `copy_structure_and_data`;',
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->copyTable($table));
    }
}
