<?php

namespace Phoenix\Tests\Database\QueryBuilder\SqliteQueryBuilder;

use Phoenix\Database\Adapter\SqliteAdapter;
use Phoenix\Database\Element\MigrationTable;
use Phoenix\Database\QueryBuilder\SqliteQueryBuilder;
use Phoenix\Exception\PhoenixException;
use Phoenix\Tests\Helpers\Adapter\SqliteCleanupAdapter;
use Phoenix\Tests\Helpers\Pdo\SqlitePdo;
use PHPUnit\Framework\TestCase;

class CopyTableTest extends TestCase
{
    private $adapter;

    protected function setUp()
    {
        $pdo = new SqlitePdo();
        $adapter = new SqliteCleanupAdapter($pdo);
        $adapter->cleanupDatabase();

        $pdo = new SqlitePdo();
        $this->adapter = new SqliteAdapter($pdo);
    }

    public function testMissingAdapter()
    {
        $table = new MigrationTable('missing_adapter');
        $table->addColumn('title', 'string');
        $table->create();

        $queryBuilder = new SqliteQueryBuilder($this->adapter);
        foreach ($queryBuilder->createTable($table) as $query) {
            $this->adapter->execute($query);
        }

        $table = new MigrationTable('missing_adapter');
        $table->copy('new_missing_adapter');

        $queryBuilder = new SqliteQueryBuilder();
        $this->expectException(PhoenixException::class);
        $this->expectExceptionMessage('Missing adapter');
        $queryBuilder->copyTable($table);
    }

    public function testCopyDefault()
    {
        $table = new MigrationTable('copy_default');
        $table->addColumn('title', 'string');
        $table->create();

        $queryBuilder = new SqliteQueryBuilder($this->adapter);
        foreach ($queryBuilder->createTable($table) as $query) {
            $this->adapter->execute($query);
        }

        $table = new MigrationTable('copy_default');
        $table->copy('new_copy_default');

        $timestamp = date('YmdHis');
        $expectedQueries = [
            'ALTER TABLE "copy_default" RENAME TO "_copy_default_old_' . $timestamp . '";',
            'CREATE TABLE "copy_default" ("id" integer PRIMARY KEY AUTOINCREMENT NOT NULL,"title" varchar(255) NOT NULL);',
            'ALTER TABLE "copy_default" RENAME TO "new_copy_default";',
            'ALTER TABLE "_copy_default_old_' . $timestamp . '" RENAME TO "copy_default";',
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->copyTable($table));
    }

    public function testCopyOnlyStructure()
    {
        $table = new MigrationTable('copy_only_structure');
        $table->addColumn('title', 'string');
        $table->create();

        $queryBuilder = new SqliteQueryBuilder($this->adapter);
        foreach ($queryBuilder->createTable($table) as $query) {
            $this->adapter->execute($query);
        }

        $table = new MigrationTable('copy_only_structure');
        $table->copy('new_copy_only_structure', MigrationTable::COPY_ONLY_STRUCTURE);

        $timestamp = date('YmdHis');
        $expectedQueries = [
            'ALTER TABLE "copy_only_structure" RENAME TO "_copy_only_structure_old_' . $timestamp . '";',
            'CREATE TABLE "copy_only_structure" ("id" integer PRIMARY KEY AUTOINCREMENT NOT NULL,"title" varchar(255) NOT NULL);',
            'ALTER TABLE "copy_only_structure" RENAME TO "new_copy_only_structure";',
            'ALTER TABLE "_copy_only_structure_old_' . $timestamp . '" RENAME TO "copy_only_structure";',
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->copyTable($table));
    }

    public function testCopyOnlyData()
    {
        $table = new MigrationTable('copy_only_data');
        $table->addColumn('title', 'string');
        $table->create();

        $queryBuilder = new SqliteQueryBuilder($this->adapter);
        foreach ($queryBuilder->createTable($table) as $query) {
            $this->adapter->execute($query);
        }

        $table = new MigrationTable('copy_only_data');
        $table->copy('new_copy_only_data', MigrationTable::COPY_ONLY_DATA);

        $expectedQueries = [
            'INSERT INTO "new_copy_only_data" SELECT * FROM "copy_only_data";',
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->copyTable($table));
    }

    public function testCopyStructureAndData()
    {
        $table = new MigrationTable('copy_structure_and_data');
        $table->addColumn('title', 'string');
        $table->create();

        $queryBuilder = new SqliteQueryBuilder($this->adapter);
        foreach ($queryBuilder->createTable($table) as $query) {
            $this->adapter->execute($query);
        }

        $table = new MigrationTable('copy_structure_and_data');
        $table->copy('new_copy_structure_and_data', MigrationTable::COPY_STRUCTURE_AND_DATA);

        $timestamp = date('YmdHis');
        $expectedQueries = [
            'ALTER TABLE "copy_structure_and_data" RENAME TO "_copy_structure_and_data_old_' . $timestamp . '";',
            'CREATE TABLE "copy_structure_and_data" ("id" integer PRIMARY KEY AUTOINCREMENT NOT NULL,"title" varchar(255) NOT NULL);',
            'INSERT INTO "copy_structure_and_data" ("id","title") SELECT "id","title" FROM "_copy_structure_and_data_old_' . $timestamp . '"',
            'ALTER TABLE "copy_structure_and_data" RENAME TO "new_copy_structure_and_data";',
            'ALTER TABLE "_copy_structure_and_data_old_' . $timestamp . '" RENAME TO "copy_structure_and_data";',
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->copyTable($table));
    }
}
