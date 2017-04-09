<?php

namespace Phoenix\Tests\Database\Adapter;

use LogicException;
use PDO;
use Phoenix\Database\Adapter\MysqlAdapter;
use Phoenix\Database\Adapter\SqliteAdapter;
use Phoenix\Database\Element\Column;
use Phoenix\Database\QueryBuilder\QueryBuilderInterface;
use Phoenix\Tests\Mock\Database\FakePdo;
use PHPUnit_Framework_TestCase;

class AdapterTest extends PHPUnit_Framework_TestCase
{
    public function testMysqlAdapter()
    {
        $pdo = new FakePdo();
        $adapter = new MysqlAdapter($pdo);
        $this->assertInstanceOf(QueryBuilderInterface::class, $adapter->getQueryBuilder());

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Not yet implemented');
        $adapter->tableInfo('table');
    }

    public function testSqliteAdapter()
    {
        $pdo = new PDO('sqlite::memory:');
        $adapter = new SqliteAdapter($pdo);
        $this->assertInstanceOf(QueryBuilderInterface::class, $adapter->getQueryBuilder());

        $pdo->query('CREATE TABLE "test" ("id" integer PRIMARY KEY AUTOINCREMENT NOT NULL,"title" varchar(255) NOT NULL);');

        $tableInfo = $adapter->tableInfo('test');
        $this->assertCount(2, $tableInfo);
        $this->assertArrayHasKey('id', $tableInfo);
        $this->assertArrayHasKey('title', $tableInfo);

        foreach ($tableInfo as $column) {
            $this->assertInstanceOf(Column::class, $column);
        }
    }
}
