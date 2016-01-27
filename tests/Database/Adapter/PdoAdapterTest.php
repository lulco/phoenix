<?php

namespace Phoenix\Tests\Database\Adapter;

use PDO;
use Phoenix\Database\Adapter\SqliteAdapter;
use Phoenix\Exception\DatabaseQueryExecuteException;
use PHPUnit_Framework_TestCase;

class PdoAdapterTest extends PHPUnit_Framework_TestCase
{
    public function testSimple()
    {
        $pdo = new PDO('sqlite::memory:');
        $adapter = new SqliteAdapter($pdo);
        $this->assertInstanceOf('\Phoenix\Database\QueryBuilder\QueryBuilderInterface', $adapter->getQueryBuilder());
        
        $adapter->startTransaction();
        $adapter->execute('CREATE TABLE "phoenix_test_table" ("id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,"title" TEXT NOT NULL,"sorting" INTEGER NOT NULL);');
        $adapter->execute('INSERT INTO "phoenix_test_table" VALUES (1, "first", 1);');
        $adapter->execute('INSERT INTO "phoenix_test_table" VALUES (2, "second", 2);');
        $adapter->commit();
    }
    
    public function testInsert()
    {
        $pdo = new PDO('sqlite::memory:');
        $adapter = new SqliteAdapter($pdo);
        $this->assertInstanceOf('\Phoenix\Database\QueryBuilder\QueryBuilderInterface', $adapter->getQueryBuilder());
        
        $adapter->execute('CREATE TABLE "phoenix_test_table" ("id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,"title" TEXT NOT NULL,"sorting" INTEGER NOT NULL);');
        $adapter->insert('phoenix_test_table', ['id' => 1, 'title' => 'first', 'sorting' => 1]);
        $adapter->insert('phoenix_test_table', ['id' => 2, 'title' => 'second', 'sorting' => 2]);
    }
    
    public function testThrowingException()
    {
        $pdo = new PDO('sqlite::memory:');
        $adapter = new SqliteAdapter($pdo);
        $this->assertInstanceOf('\Phoenix\Database\QueryBuilder\QueryBuilderInterface', $adapter->getQueryBuilder());
        
        $adapter->execute('CREATE TABLE "phoenix_test_table" ("id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,"title" TEXT NOT NULL,"sorting" INTEGER NOT NULL);');
        $this->setExpectedException('\Phoenix\Exception\DatabaseQueryExecuteException', 'SQLSTATE[HY000]: table "phoenix_test_table" already exists. Query CREATE TABLE "phoenix_test_table" ("id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,"title" TEXT NOT NULL,"sorting" INTEGER NOT NULL); fails', 1);
        $adapter->execute('CREATE TABLE "phoenix_test_table" ("id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,"title" TEXT NOT NULL,"sorting" INTEGER NOT NULL);');
    }
    
    public function testRollback()
    {
        $pdo = new PDO('sqlite::memory:');
        $adapter = new SqliteAdapter($pdo);
        $this->assertInstanceOf('\Phoenix\Database\QueryBuilder\QueryBuilderInterface', $adapter->getQueryBuilder());
        
        $adapter->startTransaction();
        try {
            $adapter->execute('CREATE TABLE "phoenix_test_table" ("id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,"title" TEXT NOT NULL,"sorting" INTEGER NOT NULL);');
            $adapter->execute('CREATE TABLE "phoenix_test_table" ("id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,"title" TEXT NOT NULL,"sorting" INTEGER NOT NULL);');
            $adapter->commit();
        } catch (DatabaseQueryExecuteException $e) {
            $this->assertEquals(1, $e->getCode());
            $this->assertEquals('SQLSTATE[HY000]: table "phoenix_test_table" already exists. Query CREATE TABLE "phoenix_test_table" ("id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,"title" TEXT NOT NULL,"sorting" INTEGER NOT NULL); fails', $e->getMessage());
            $adapter->rollback();
        }
    }
}
