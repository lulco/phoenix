<?php

declare(strict_types=1);

namespace Phoenix\Tests\Database\QueryBuilder\PgsqlQueryBuilder;

use Phoenix\Database\Adapter\PgsqlAdapter;
use Phoenix\Database\Element\MigrationTable;
use Phoenix\Database\QueryBuilder\PgsqlQueryBuilder;
use Phoenix\Tests\Helpers\Pdo\PgsqlPdo;
use PHPUnit\Framework\TestCase;

final class RenameColumnTest extends TestCase
{
    private PgsqlAdapter $adapter;

    protected function setUp(): void
    {
        $pdo = new PgsqlPdo(getenv('PHOENIX_PGSQL_DATABASE'));
        $this->adapter = new PgsqlAdapter($pdo);
    }

    public function testSimpleRenameColumn(): void
    {
        $queryBuilder = new PgsqlQueryBuilder($this->adapter);

        $table = new MigrationTable('test_table');
        $table->renameColumn('asdf', 'alias');

        $expectedQueries = [
            'ALTER TABLE "test_table" RENAME COLUMN "asdf" TO "alias";',
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->alterTable($table));
    }
}
