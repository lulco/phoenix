<?php

namespace Phoenix\Tests\Database\Adapter;

use DateTime;
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
        
        $adapter->execute('CREATE TABLE "phoenix_test_table" ("id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,"title" TEXT NOT NULL,"sorting" INTEGER NOT NULL);');
        $adapter->execute('INSERT INTO "phoenix_test_table" VALUES (1, "first", 1);');
        $adapter->execute('INSERT INTO "phoenix_test_table" VALUES (2, "second", 2);');
    }
    
    public function testTransaction()
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
        
        $adapter->execute('CREATE TABLE "phoenix_test_table" ("id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,"title" TEXT NOT NULL,"date" TEXT NOT NULL);');
        $this->assertEquals(1, $adapter->insert('phoenix_test_table', ['id' => 1, 'title' => 'first', 'date' => new DateTime()]));
        $this->assertEquals(2, $adapter->insert('phoenix_test_table', ['id' => 2, 'title' => 'second', 'date' => new DateTime()]));
        
        $this->setExpectedException('\Phoenix\Exception\DatabaseQueryExecuteException', 'SQLSTATE[HY000]: no such table: phoenix_non_exist_test_table.', 1);
        $adapter->insert('phoenix_non_exist_test_table', ['id' => 1, 'title' => 'first', 'date' => new DateTime()]);
    }
    
    public function testInsertDataToNotExistColumn()
    {
        $pdo = new PDO('sqlite::memory:');
        $adapter = new SqliteAdapter($pdo);
        
        $adapter->execute('CREATE TABLE "phoenix_test_table" ("id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,"title" TEXT NOT NULL,"sorting" INTEGER NOT NULL);');

        $this->setExpectedException('\Phoenix\Exception\DatabaseQueryExecuteException', 'SQLSTATE[HY000]: table phoenix_test_table has no column named unknown.', 1);
        $adapter->insert('phoenix_test_table', ['id' => 1, 'unknown' => 'first', 'sorting' => 1]);
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
    
    public function testUpdate()
    {
        $pdo = new PDO('sqlite::memory:');
        $adapter = new SqliteAdapter($pdo);
        $this->assertInstanceOf('\Phoenix\Database\QueryBuilder\QueryBuilderInterface', $adapter->getQueryBuilder());
        
        $adapter->execute('CREATE TABLE "phoenix_test_table" ("id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,"title" TEXT NOT NULL,"date" TEXT NOT NULL);');
        $this->assertEquals(1, $adapter->insert('phoenix_test_table', ['id' => 1, 'title' => 'first', 'date' => new DateTime()]));
        $item = $adapter->fetch('phoenix_test_table', 'title', ['id' => 1]);
        $this->assertEquals('first', $item['title']);
        $this->assertTrue($adapter->update('phoenix_test_table', ['id' => 1, 'title' => 'second', 'date' => new DateTime()], ['id' => 1]));
        $item = $adapter->fetch('phoenix_test_table', 'title', ['id' => 1], ['title']);
        $this->assertEquals('second', $item['title']);
        $this->assertTrue($adapter->update('phoenix_test_table', ['id' => 1, 'title' => 'third', 'date' => new DateTime()], [], 'id = 1'));
        $items = $adapter->fetchAll('phoenix_test_table', 'title', ['id' => 1], null, ['title' => 'DESC'], ['id']);
        $this->assertEquals('third', $items[0]['title']);

        $this->setExpectedException('\Phoenix\Exception\DatabaseQueryExecuteException', 'SQLSTATE[HY000]: no such table: phoenix_non_exist_test_table.', 1);
        $adapter->update('phoenix_non_exist_test_table', ['id' => 1, 'title' => 'first', 'date' => new DateTime()]);
    }
    
    public function testSelect()
    {
        $pdo = new PDO('sqlite::memory:');
        $adapter = new SqliteAdapter($pdo);
        $this->assertInstanceOf('\Phoenix\Database\QueryBuilder\QueryBuilderInterface', $adapter->getQueryBuilder());
        
        $adapter->execute('CREATE TABLE "phoenix_test_table" ("id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,"title" TEXT NOT NULL,"date" TEXT NOT NULL);');
        $this->assertEquals(1, $adapter->insert('phoenix_test_table', ['id' => 1, 'title' => 'first', 'date' => new DateTime()]));
        $this->assertEquals(2, $adapter->insert('phoenix_test_table', ['id' => 2, 'title' => 'second', 'date' => new DateTime()]));
        
        $items = $adapter->select('SELECT * FROM `phoenix_test_table`');
        $this->assertCount(2, $items);
        foreach ($items as $item) {
            $this->assertCount(3, $item);
            $this->assertArrayHasKey('id', $item);
            $this->assertArrayHasKey('title', $item);
            $this->assertArrayHasKey('date', $item);
        }
        
        $this->setExpectedException('\InvalidArgumentException', 'Only select query can be executed in select method');
        $adapter->select('INSERT INTO `phoenix_test_table` (`id`, `title`, `date`) VALUES (3, "third", ' . (new DateTime)->format('Y-m-d H:i:s') . ')');
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
