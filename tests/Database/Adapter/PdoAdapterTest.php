<?php

namespace Phoenix\Tests\Database\Adapter;

use DateTime;
use PDO;
use Phoenix\Database\Adapter\SqliteAdapter;
use Phoenix\Exception\DatabaseQueryExecuteException;
use PHPUnit_Framework_TestCase;

class PdoAdapterTest extends PHPUnit_Framework_TestCase
{
    private $adapter;

    protected function setUp()
    {
        parent::setUp();
        $pdo = new PDO('sqlite::memory:');
        $this->adapter = new SqliteAdapter($pdo);
    }

    public function testSimple()
    {
        $this->assertInstanceOf('\Phoenix\Database\QueryBuilder\QueryBuilderInterface', $this->adapter->getQueryBuilder());
        
        $this->assertInstanceOf('\PDOStatement', $this->adapter->execute('CREATE TABLE "phoenix_test_table" ("id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,"title" TEXT NOT NULL,"sorting" INTEGER NOT NULL);'));
        $this->assertInstanceOf('\PDOStatement', $this->adapter->execute('INSERT INTO "phoenix_test_table" VALUES (1, "first", 1);'));
        $this->assertInstanceOf('\PDOStatement', $this->adapter->execute('INSERT INTO "phoenix_test_table" VALUES (2, "second", 2);'));
    }
    
    public function testTransaction()
    {
        $this->assertInstanceOf('\Phoenix\Database\QueryBuilder\QueryBuilderInterface', $this->adapter->getQueryBuilder());
        
        $this->adapter->startTransaction();
        $this->adapter->execute('CREATE TABLE "phoenix_test_table" ("id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,"title" TEXT NOT NULL,"sorting" INTEGER NOT NULL);');
        $this->adapter->execute('INSERT INTO "phoenix_test_table" VALUES (1, "first", 1);');
        $this->adapter->execute('INSERT INTO "phoenix_test_table" VALUES (2, "second", 2);');
        $this->adapter->commit();
    }
    
    public function testInsert()
    {
        $this->adapter->execute('CREATE TABLE "phoenix_test_table" ("id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,"title" TEXT NOT NULL,"date" TEXT NOT NULL);');
        $this->assertEquals(1, $this->adapter->insert('phoenix_test_table', ['id' => 1, 'title' => 'first', 'date' => new DateTime()]));
        $this->assertEquals(2, $this->adapter->insert('phoenix_test_table', ['id' => 2, 'title' => 'second', 'date' => new DateTime()]));
        
        $this->assertCount(2, $this->adapter->fetchAll('phoenix_test_table'));
        
        $this->setExpectedException('\Phoenix\Exception\DatabaseQueryExecuteException', 'SQLSTATE[HY000]: no such table: phoenix_non_exist_test_table.', 1);
        $this->adapter->insert('phoenix_non_exist_test_table', ['id' => 1, 'title' => 'first', 'date' => new DateTime()]);
    }
    
    public function testInsertDataToNotExistColumn()
    {
        $this->adapter->execute('CREATE TABLE "phoenix_test_table" ("id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,"title" TEXT NOT NULL,"sorting" INTEGER NOT NULL);');

        $this->setExpectedException('\Phoenix\Exception\DatabaseQueryExecuteException', 'SQLSTATE[HY000]: table phoenix_test_table has no column named unknown.', 1);
        $this->adapter->insert('phoenix_test_table', ['id' => 1, 'unknown' => 'first', 'sorting' => 1]);
    }
    
    public function testMultiInsert()
    {
        $this->adapter->execute('CREATE TABLE "phoenix_test_table" ("id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,"title" TEXT NOT NULL,"date" TEXT NOT NULL);');
        $this->assertEquals(2, $this->adapter->insert('phoenix_test_table', [['id' => 1, 'title' => 'first', 'date' => new DateTime()], ['id' => 2, 'title' => 'second', 'date' => new DateTime()]]));
        $this->assertCount(2, $this->adapter->fetchAll('phoenix_test_table'));
    }
    
    public function testThrowingException()
    {
        $this->assertInstanceOf('\Phoenix\Database\QueryBuilder\QueryBuilderInterface', $this->adapter->getQueryBuilder());
        
        $this->adapter->execute('CREATE TABLE "phoenix_test_table" ("id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,"title" TEXT NOT NULL,"sorting" INTEGER NOT NULL);');
        $this->setExpectedException('\Phoenix\Exception\DatabaseQueryExecuteException', 'SQLSTATE[HY000]: table "phoenix_test_table" already exists. Query CREATE TABLE "phoenix_test_table" ("id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,"title" TEXT NOT NULL,"sorting" INTEGER NOT NULL); fails', 1);
        $this->adapter->execute('CREATE TABLE "phoenix_test_table" ("id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,"title" TEXT NOT NULL,"sorting" INTEGER NOT NULL);');
    }
    
    public function testUpdate()
    {
        $this->assertInstanceOf('\Phoenix\Database\QueryBuilder\QueryBuilderInterface', $this->adapter->getQueryBuilder());
        
        $this->adapter->execute('CREATE TABLE "phoenix_test_table" ("id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,"title" TEXT NOT NULL,"date" TEXT NOT NULL);');
        $this->assertEquals(1, $this->adapter->insert('phoenix_test_table', ['id' => 1, 'title' => 'first', 'date' => new DateTime()]));
        $item = $this->adapter->fetch('phoenix_test_table', 'title', ['id' => 1]);
        $this->assertEquals('first', $item['title']);
        $this->assertTrue($this->adapter->update('phoenix_test_table', ['id' => 1, 'title' => 'second', 'date' => new DateTime()], ['id' => 1]));
        $item = $this->adapter->fetch('phoenix_test_table', 'title', ['id' => 1], ['title']);
        $this->assertEquals('second', $item['title']);
        $this->assertTrue($this->adapter->update('phoenix_test_table', ['id' => 1, 'title' => 'third', 'date' => new DateTime()], [], 'id = 1'));
        $items = $this->adapter->fetchAll('phoenix_test_table', 'title', ['id' => 1], null, ['title' => 'DESC'], ['id']);
        $this->assertEquals('third', $items[0]['title']);

        $this->setExpectedException('\Phoenix\Exception\DatabaseQueryExecuteException', 'SQLSTATE[HY000]: no such table: phoenix_non_exist_test_table.', 1);
        $this->adapter->update('phoenix_non_exist_test_table', ['id' => 1, 'title' => 'first', 'date' => new DateTime()]);
    }
    
    public function testSelect()
    {
        $this->assertInstanceOf('\Phoenix\Database\QueryBuilder\QueryBuilderInterface', $this->adapter->getQueryBuilder());
        
        $this->adapter->execute('CREATE TABLE "phoenix_test_table" ("id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,"title" TEXT NOT NULL,"date" TEXT NOT NULL);');
        $this->assertEquals(1, $this->adapter->insert('phoenix_test_table', ['id' => 1, 'title' => 'first', 'date' => new DateTime()]));
        $this->assertEquals(2, $this->adapter->insert('phoenix_test_table', ['id' => 2, 'title' => 'second', 'date' => new DateTime()]));
        
        $items = $this->adapter->select('SELECT * FROM `phoenix_test_table`');
        $this->assertCount(2, $items);
        foreach ($items as $item) {
            $this->assertCount(3, $item);
            $this->assertArrayHasKey('id', $item);
            $this->assertArrayHasKey('title', $item);
            $this->assertArrayHasKey('date', $item);
        }
        
        $this->setExpectedException('\InvalidArgumentException', 'Only select query can be executed in select method');
        $this->adapter->select('INSERT INTO `phoenix_test_table` (`id`, `title`, `date`) VALUES (3, "third", ' . (new DateTime)->format('Y-m-d H:i:s') . ')');
    }
    
    public function testFetch()
    {
        $this->assertInstanceOf('\Phoenix\Database\QueryBuilder\QueryBuilderInterface', $this->adapter->getQueryBuilder());
        
        $this->adapter->execute('CREATE TABLE "phoenix_test_table" ("id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,"title" TEXT NOT NULL,"date" TEXT NOT NULL);');
        $this->assertEquals(1, $this->adapter->insert('phoenix_test_table', ['id' => 1, 'title' => 'first', 'date' => new DateTime()]));
        $this->assertEquals(2, $this->adapter->insert('phoenix_test_table', ['id' => 2, 'title' => 'second', 'date' => new DateTime()]));
        
        $items = $this->adapter->fetchAll('phoenix_test_table');
        $this->assertCount(2, $items);
        foreach ($items as $item) {
            $this->assertCount(3, $item);
            $this->assertArrayHasKey('id', $item);
            $this->assertArrayHasKey('title', $item);
            $this->assertArrayHasKey('date', $item);
        }
        
        $this->setExpectedException('\Phoenix\Exception\DatabaseQueryExecuteException', 'SQLSTATE[HY000]: no such table: phoenix_non_exist_test_table.');
        $this->adapter->fetchAll('phoenix_non_exist_test_table');
    }
    
    public function testDelete()
    {
        $this->adapter->execute('CREATE TABLE "phoenix_test_table" ("id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,"title" TEXT NOT NULL,"date" TEXT NOT NULL);');
        $this->assertEquals(1, $this->adapter->insert('phoenix_test_table', ['id' => 1, 'title' => 'first', 'date' => new DateTime()]));
        $this->assertEquals(2, $this->adapter->insert('phoenix_test_table', ['id' => 2, 'title' => 'second', 'date' => new DateTime()]));
        
        $items = $this->adapter->fetchAll('phoenix_test_table');
        $this->assertCount(2, $items);
        
        $this->adapter->delete('phoenix_test_table');
        $items = $this->adapter->fetchAll('phoenix_test_table');
        $this->assertCount(0, $items);
        
        $this->assertEquals(1, $this->adapter->insert('phoenix_test_table', ['id' => 1, 'title' => 'first', 'date' => new DateTime()]));
        $this->assertEquals(2, $this->adapter->insert('phoenix_test_table', ['id' => 2, 'title' => 'second', 'date' => new DateTime()]));
        $items = $this->adapter->fetchAll('phoenix_test_table');
        $this->assertCount(2, $items);
        
        $this->adapter->delete('phoenix_test_table', ['id' => 2]);
        $items = $this->adapter->fetchAll('phoenix_test_table');
        $this->assertCount(1, $items);
        
        $this->assertEquals(2, $this->adapter->insert('phoenix_test_table', ['id' => 2, 'title' => 'second', 'date' => new DateTime()]));
        $this->adapter->delete('phoenix_test_table', [], 'id < 3');
        $items = $this->adapter->fetchAll('phoenix_test_table');
        $this->assertCount(0, $items);
        
        $this->setExpectedException('\Phoenix\Exception\DatabaseQueryExecuteException', 'SQLSTATE[HY000]: no such table: phoenix_non_exist_test_table.', 1);
        $this->adapter->delete('phoenix_non_exist_test_table');
    }
    
    public function testRollback()
    {
        $this->assertInstanceOf('\Phoenix\Database\QueryBuilder\QueryBuilderInterface', $this->adapter->getQueryBuilder());
        
        $this->adapter->startTransaction();
        try {
            $this->adapter->execute('CREATE TABLE "phoenix_test_table" ("id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,"title" TEXT NOT NULL,"sorting" INTEGER NOT NULL);');
            $this->adapter->execute('CREATE TABLE "phoenix_test_table" ("id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,"title" TEXT NOT NULL,"sorting" INTEGER NOT NULL);');
            $this->adapter->commit();
        } catch (DatabaseQueryExecuteException $e) {
            $this->assertEquals(1, $e->getCode());
            $this->assertEquals('SQLSTATE[HY000]: table "phoenix_test_table" already exists. Query CREATE TABLE "phoenix_test_table" ("id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,"title" TEXT NOT NULL,"sorting" INTEGER NOT NULL); fails', $e->getMessage());
            $this->adapter->rollback();
        }
    }
}
