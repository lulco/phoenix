<?php

namespace Phoenix\Tests\Database\Adapter;

use PDO;
use Phoenix\Database\Adapter\SqliteAdapter;
use Phoenix\Database\Element\Column;
use Phoenix\Database\QueryBuilder\QueryBuilderInterface;
use PHPUnit_Framework_TestCase;

class AdapterTest extends PHPUnit_Framework_TestCase
{
    public function testSqliteAdapter()
    {
        $pdo = new PDO('sqlite::memory:');
        $adapter = new SqliteAdapter($pdo);

        $pdo->query('CREATE TABLE "test" ("id" integer PRIMARY KEY AUTOINCREMENT NOT NULL,"title" varchar(255) NOT NULL);');

        $structure = $adapter->getStructure();
        $tableInfo = $structure->getTable('test')->getColumns();
        $this->assertCount(2, $tableInfo);
        $this->assertArrayHasKey('id', $tableInfo);
        $this->assertArrayHasKey('title', $tableInfo);

        foreach ($tableInfo as $column) {
            $this->assertInstanceOf(Column::class, $column);
        }
        $this->assertInstanceOf(QueryBuilderInterface::class, $adapter->getQueryBuilder());
    }
}
