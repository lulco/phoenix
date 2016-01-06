<?php

namespace Phoenix\Tests;

use Phoenix\Tests\Database\Adapter\DummyAdapter;
use Phoenix\Tests\Migration\AddColumnAndAddIndexExceptionsMigration;
use Phoenix\Tests\Migration\CreateAndDropExceptionsMigration;
use Phoenix\Tests\Migration\CreateAndDropTableMigration;
use Phoenix\Tests\Migration\DoubleUseOfTableExceptionMigration;
use Phoenix\Tests\Migration\SimpleQueriesMigration;
use PHPUnit_Framework_TestCase;

class MigrationWithDummyAdapterTest extends PHPUnit_Framework_TestCase
{
    public function testSimpleQueries()
    {
        $adapter = new DummyAdapter();
        $migration = new SimpleQueriesMigration($adapter);
        $result = $migration->migrate();
        $this->assertTrue(is_array($result));
        foreach ($result as $one) {
            $this->assertTrue(is_string($one));
            $this->assertTrue(strpos($one, 'Query') === 0);
            $this->assertEquals(substr($one, -8, 8), 'executed');
        }
        
        $adapter = new DummyAdapter();
        $migration = new SimpleQueriesMigration($adapter);
        $result = $migration->rollback();
        $this->assertTrue(is_array($result));
        foreach ($result as $one) {
            $this->assertTrue(is_string($one));
            $this->assertTrue(strpos($one, 'Query') === 0);
            $this->assertEquals(substr($one, -8, 8), 'executed');
        }
    }
    
    public function testCreateAndDropTable()
    {
        $adapter = new DummyAdapter();
        $migration = new CreateAndDropTableMigration($adapter);
        $result = $migration->migrate();
        $this->assertTrue(is_array($result));
        foreach ($result as $one) {
            $this->assertTrue(is_string($one));
            $this->assertTrue(strpos($one, 'Query') === 0);
            $this->assertEquals(substr($one, -8, 8), 'executed');
        }
        
        $adapter = new DummyAdapter();
        $migration = new CreateAndDropTableMigration($adapter);
        $result = $migration->rollback();
        $this->assertTrue(is_array($result));
        foreach ($result as $one) {
            $this->assertTrue(is_string($one));
            $this->assertTrue(strpos($one, 'Query') === 0);
            $this->assertEquals(substr($one, -8, 8), 'executed');
        }
    }
    
    public function testCreateTableException()
    {
        $adapter = new DummyAdapter();
        $migration = new CreateAndDropExceptionsMigration($adapter);
        $this->setExpectedException('\Phoenix\Exception\IncorrectMethodUsageException', 'Wrong use of method create(). Use method table() first.');
        $result = $migration->migrate();
    }
    
    public function testDropTableException()
    {
        $adapter = new DummyAdapter();
        $migration = new CreateAndDropExceptionsMigration($adapter);
        $this->setExpectedException('\Phoenix\Exception\IncorrectMethodUsageException', 'Wrong use of method drop(). Use method table() first.');
        $result = $migration->rollback();
    }
    
    public function testAddColumnException()
    {
        $adapter = new DummyAdapter();
        $migration = new AddColumnAndAddIndexExceptionsMigration($adapter);
        $this->setExpectedException('\Phoenix\Exception\IncorrectMethodUsageException', 'Wrong use of method addColumn(). Use method table() first.');
        $result = $migration->migrate();
    }
    
    public function testAddIndexException()
    {
        $adapter = new DummyAdapter();
        $migration = new AddColumnAndAddIndexExceptionsMigration($adapter);
        $this->setExpectedException('\Phoenix\Exception\IncorrectMethodUsageException', 'Wrong use of method addIndex(). Use method table() first.');
        $result = $migration->rollback();
    }
    
    public function testDoubleUseOfTableException()
    {
        $adapter = new DummyAdapter();
        $migration = new DoubleUseOfTableExceptionMigration($adapter);
        $this->setExpectedException('\Phoenix\Exception\IncorrectMethodUsageException', 'Wrong use of method table(). Use one of methods create(), drop() first.');
        $result = $migration->migrate();
    }
    
    public function testTrippleUseOfTableException()
    {
        $adapter = new DummyAdapter();
        $migration = new DoubleUseOfTableExceptionMigration($adapter);
        $this->setExpectedException('\Phoenix\Exception\IncorrectMethodUsageException', 'Wrong use of method table(). Use one of methods create(), drop() first.');
        $result = $migration->rollback();
    }
}
