<?php

namespace Phoenix\Tests\Database\Adapter;

use InvalidArgumentException;
use Phoenix\Database\QueryBuilder\QueryBuilderInterface;
use Phoenix\Exception\DatabaseQueryExecuteException;
use Phoenix\Tests\Helpers\Adapter\MysqlCleanupAdapter;
use Phoenix\Tests\Helpers\Pdo\MysqlPdo;
use PHPUnit\Framework\TestCase;

class PdoAdapterTest extends TestCase
{
    private $adapter;

    protected function setUp(): void
    {
        $pdo = new MysqlPdo();
        $adapter = new MysqlCleanupAdapter($pdo);
        $adapter->cleanupDatabase();

        $pdo = new MysqlPdo(getenv('PHOENIX_MYSQL_DATABASE'));
        $this->adapter = new MysqlCleanupAdapter($pdo);
    }

    public function testTransaction()
    {
        $this->assertInstanceOf(QueryBuilderInterface::class, $this->adapter->getQueryBuilder());

        $this->adapter->startTransaction();
        $this->adapter->query('CREATE TABLE `phoenix_test_table` (`id` int NOT NULL AUTO_INCREMENT,`title` varchar(255) NOT NULL,PRIMARY KEY (`id`));');
        $this->adapter->query('INSERT INTO `phoenix_test_table` VALUES (1, "first");');
        $this->adapter->query('INSERT INTO `phoenix_test_table` VALUES (2, "second");');
        $this->adapter->commit();
    }

    public function testInsert()
    {
        $this->adapter->query('CREATE TABLE `phoenix_test_table` (`id` int NOT NULL AUTO_INCREMENT,`title` varchar(255) NOT NULL,PRIMARY KEY (`id`));');
        $this->assertEquals(1, $this->adapter->insert('phoenix_test_table', ['id' => 1, 'title' => 'first']));
        $this->assertEquals(2, $this->adapter->insert('phoenix_test_table', ['id' => 2, 'title' => 'second']));

        $this->assertCount(2, $this->adapter->fetchAll('phoenix_test_table'));
    }

    public function testInsertToNonExistingTable()
    {
        $this->expectException(DatabaseQueryExecuteException::class);
        $this->adapter->insert('phoenix_non_exist_test_table', ['id' => 1, 'title' => 'first']);
    }

    public function testMultiInsert()
    {
        $this->adapter->query('CREATE TABLE `phoenix_test_table` (`id` int NOT NULL AUTO_INCREMENT,`title` varchar(255) NOT NULL,PRIMARY KEY (`id`));');
        $this->assertEquals(2, $this->adapter->insert('phoenix_test_table', [['id' => 1, 'title' => 'first'], ['id' => 2, 'title' => 'second']]));
        $this->assertCount(2, $this->adapter->fetchAll('phoenix_test_table'));
    }

    public function testUpdate()
    {
        $this->adapter->query('CREATE TABLE `phoenix_test_table` (`id` int NOT NULL AUTO_INCREMENT,`title` varchar(255) NOT NULL,PRIMARY KEY (`id`));');
        $this->assertEquals(1, $this->adapter->insert('phoenix_test_table', ['id' => 1, 'title' => 'first']));
        $item = $this->adapter->fetch('phoenix_test_table', ['title'], ['id' => 1]);
        $this->assertEquals('first', $item['title']);

        $this->assertTrue($this->adapter->update('phoenix_test_table', ['id' => 1, 'title' => 'second'], ['id' => 1]));
        $item = $this->adapter->fetch('phoenix_test_table', ['title'], ['id' => 1], ['title']);
        $this->assertEquals('second', $item['title']);

        $this->assertTrue($this->adapter->update('phoenix_test_table', ['id' => 1, 'title' => 'third'], [], 'id = 1'));
        $items = $this->adapter->fetchAll('phoenix_test_table', ['title'], ['id' => 1], null, ['title' => 'DESC'], ['id']);
        $this->assertEquals('third', $items[0]['title']);

        $this->assertEquals(2, $this->adapter->insert('phoenix_test_table', ['id' => 2, 'title' => 'fourth']));
        $this->assertTrue($this->adapter->update('phoenix_test_table', ['title' => 'multi update'], ['id' => [1, 2]]));

        $items = $this->adapter->fetchAll('phoenix_test_table', ['title'], ['id' => [1, 2]]);
        $this->assertCount(2, $items);
        foreach ($items as $item) {
            $this->assertEquals('multi update', $item['title']);
        }
    }

    public function testUpdateItemInNonExistingTable()
    {
        $this->expectException(DatabaseQueryExecuteException::class);
        $this->adapter->update('phoenix_non_exist_test_table', ['id' => 1, 'title' => 'first']);
    }

    public function testSelect()
    {
        $this->adapter->query('CREATE TABLE `phoenix_test_table` (`id` int NOT NULL AUTO_INCREMENT,`title` varchar(255) NOT NULL,PRIMARY KEY (`id`));');
        $this->assertEquals(1, $this->adapter->insert('phoenix_test_table', ['id' => 1, 'title' => 'first']));
        $this->assertEquals(2, $this->adapter->insert('phoenix_test_table', ['id' => 2, 'title' => 'second']));

        $items = $this->adapter->select('SELECT * FROM `phoenix_test_table`');
        $this->assertCount(2, $items);
        foreach ($items as $item) {
            $this->assertCount(2, $item);
            $this->assertArrayHasKey('id', $item);
            $this->assertArrayHasKey('title', $item);
        }

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Only select query can be executed in select method');
        $this->adapter->select('INSERT INTO `phoenix_test_table` (`id`, `title`) VALUES (3, "third")');
    }

    public function testFetchAll()
    {
        $this->adapter->query('CREATE TABLE `phoenix_test_table` (`id` int NOT NULL AUTO_INCREMENT,`title` varchar(255) NOT NULL,PRIMARY KEY (`id`));');
        $this->assertEquals(1, $this->adapter->insert('phoenix_test_table', ['id' => 1, 'title' => 'first']));
        $this->assertEquals(2, $this->adapter->insert('phoenix_test_table', ['id' => 2, 'title' => 'second']));

        $items = $this->adapter->fetchAll('phoenix_test_table');
        $this->assertCount(2, $items);
        foreach ($items as $item) {
            $this->assertCount(2, $item);
            $this->assertArrayHasKey('id', $item);
            $this->assertArrayHasKey('title', $item);
        }
    }

    public function testDelete()
    {
        $this->adapter->query('CREATE TABLE `phoenix_test_table` (`id` int NOT NULL AUTO_INCREMENT,`title` varchar(255) NOT NULL,PRIMARY KEY (`id`));');
        $this->assertEquals(1, $this->adapter->insert('phoenix_test_table', ['id' => 1, 'title' => 'first']));
        $this->assertEquals(2, $this->adapter->insert('phoenix_test_table', ['id' => 2, 'title' => 'second']));

        $items = $this->adapter->fetchAll('phoenix_test_table');
        $this->assertCount(2, $items);

        $this->adapter->delete('phoenix_test_table');
        $items = $this->adapter->fetchAll('phoenix_test_table');
        $this->assertCount(0, $items);

        $this->assertEquals(1, $this->adapter->insert('phoenix_test_table', ['id' => 1, 'title' => 'first']));
        $this->assertEquals(2, $this->adapter->insert('phoenix_test_table', ['id' => 2, 'title' => 'second']));
        $items = $this->adapter->fetchAll('phoenix_test_table');
        $this->assertCount(2, $items);

        $this->adapter->delete('phoenix_test_table', ['id' => 2]);
        $items = $this->adapter->fetchAll('phoenix_test_table');
        $this->assertCount(1, $items);

        $this->assertEquals(2, $this->adapter->insert('phoenix_test_table', ['id' => 2, 'title' => 'second']));
        $this->adapter->delete('phoenix_test_table', [], 'id < 3');
        $items = $this->adapter->fetchAll('phoenix_test_table');
        $this->assertCount(0, $items);

        $this->assertEquals(1, $this->adapter->insert('phoenix_test_table', ['id' => 1, 'title' => 'first']));
        $this->assertEquals(2, $this->adapter->insert('phoenix_test_table', ['id' => 2, 'title' => 'second']));
        $items = $this->adapter->fetchAll('phoenix_test_table');
        $this->assertCount(2, $items);

        $this->adapter->delete('phoenix_test_table', ['id' => [1, 2]]);
    }

    public function testDeleteFromNonExistingTable()
    {
        $this->expectException(DatabaseQueryExecuteException::class);
        $this->adapter->delete('phoenix_non_exist_test_table');
    }

    public function testRollback()
    {
        $this->assertInstanceOf(QueryBuilderInterface::class, $this->adapter->getQueryBuilder());

        $this->adapter->startTransaction();
        try {
            $this->adapter->query('CREATE TABLE `phoenix_test_table` (`id` int NOT NULL AUTO_INCREMENT,`title` varchar(255) NOT NULL,PRIMARY KEY (`id`));');
            $this->adapter->query('CREATE TABLE `phoenix_test_table` (`id` int NOT NULL AUTO_INCREMENT,`title` varchar(255) NOT NULL,PRIMARY KEY (`id`));');
            $this->adapter->commit();
        } catch (DatabaseQueryExecuteException $e) {
            $this->assertEquals(1050, $e->getCode());
            $this->assertEquals('SQLSTATE[42S01]: Table \'phoenix_test_table\' already exists. Query CREATE TABLE `phoenix_test_table` (`id` int NOT NULL AUTO_INCREMENT,`title` varchar(255) NOT NULL,PRIMARY KEY (`id`)); fails', $e->getMessage());
            $this->adapter->rollback();
        }
    }
}
