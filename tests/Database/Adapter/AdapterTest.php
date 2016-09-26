<?php

namespace Phoenix\Tests\Database\Adapter;

use PDO;
use Phoenix\Database\Adapter\MysqlAdapter;
use Phoenix\Database\Adapter\PgsqlAdapter;
use Phoenix\Database\Adapter\SqliteAdapter;
use Phoenix\Tests\Mock\Database\FakePdo;
use PHPUnit_Framework_TestCase;

class AdapterTest extends PHPUnit_Framework_TestCase
{
    public function testMysqlAdapter()
    {
        $pdo = new FakePdo();
        $adapter = new MysqlAdapter($pdo);
        $this->assertInstanceOf('\Phoenix\Database\QueryBuilder\QueryBuilderInterface', $adapter->getQueryBuilder());

        $this->setExpectedException('\LogicException', 'Not yet implemented');
        $adapter->tableInfo('table');
    }

    public function testSqliteAdapter()
    {
        $pdo = new PDO('sqlite::memory:');
        $adapter = new SqliteAdapter($pdo);
        $this->assertInstanceOf('\Phoenix\Database\QueryBuilder\QueryBuilderInterface', $adapter->getQueryBuilder());

        $pdo->query('CREATE TABLE "test" ("id" integer PRIMARY KEY AUTOINCREMENT NOT NULL,"title" varchar(255) NOT NULL);');

        $tableInfo = $adapter->tableInfo('test');
        $this->assertCount(2, $tableInfo);
        $this->assertArrayHasKey('id', $tableInfo);
        $this->assertArrayHasKey('title', $tableInfo);

        foreach ($tableInfo as $column) {
            $this->assertInstanceOf('\Phoenix\Database\Element\Column', $column);
        }
    }
}
