<?php

declare(strict_types=1);

namespace Phoenix\Tests\Database\QueryBuilder\PgsqlQueryBuilder;

use Phoenix\Database\Adapter\PgsqlAdapter;
use Phoenix\Database\Element\MigrationTable;
use Phoenix\Database\QueryBuilder\PgsqlQueryBuilder;
use Phoenix\Tests\Helpers\Pdo\PgsqlPdo;
use PHPUnit\Framework\TestCase;

final class CopyTableTest extends TestCase
{
    private PgsqlAdapter $adapter;

    protected function setUp(): void
    {
        $pdo = new PgsqlPdo(getenv('PHOENIX_PGSQL_DATABASE'));
        $this->adapter = new PgsqlAdapter($pdo);
    }

    public function testCopyDefault(): void
    {
        $table = new MigrationTable('copy_default');
        $table->copy('new_copy_default');

        $queryBuilder = new PgsqlQueryBuilder($this->adapter);
        $expectedQueries = [
            'CREATE TABLE "new_copy_default" AS TABLE "copy_default" WITH NO DATA;',
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->copyTable($table));
    }

    public function testCopyOnlyStructure(): void
    {
        $table = new MigrationTable('copy_only_structure');
        $table->copy('new_copy_only_structure', MigrationTable::COPY_ONLY_STRUCTURE);

        $queryBuilder = new PgsqlQueryBuilder($this->adapter);
        $expectedQueries = [
            'CREATE TABLE "new_copy_only_structure" AS TABLE "copy_only_structure" WITH NO DATA;',
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->copyTable($table));
    }

    public function testCopyOnlyData(): void
    {
        $table = new MigrationTable('copy_only_data');
        $table->copy('new_copy_only_data', MigrationTable::COPY_ONLY_DATA);

        $queryBuilder = new PgsqlQueryBuilder($this->adapter);
        $expectedQueries = [
            'INSERT INTO "new_copy_only_data" SELECT * FROM "copy_only_data";',
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->copyTable($table));
    }

    public function testCopyStructureAndData(): void
    {
        $table = new MigrationTable('copy_structure_and_data');
        $table->copy('new_copy_structure_and_data', MigrationTable::COPY_STRUCTURE_AND_DATA);

        $queryBuilder = new PgsqlQueryBuilder($this->adapter);
        $expectedQueries = [
            'CREATE TABLE "new_copy_structure_and_data" AS TABLE "copy_structure_and_data" WITH DATA;',
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->copyTable($table));
    }
}
