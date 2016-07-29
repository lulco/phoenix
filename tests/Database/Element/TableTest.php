<?php

namespace Phoenix\Tests\Database\Element;

use Phoenix\Database\Element\Column;
use Phoenix\Database\Element\ForeignKey;
use Phoenix\Database\Element\Index;
use Phoenix\Database\Element\Table;
use PHPUnit_Framework_TestCase;

class TableTest extends PHPUnit_Framework_TestCase
{
    public function testDefaultConstruct()
    {
        $table = new Table('test');
        $table->addPrimary(true);
        $this->assertEquals('test', $table->getName());
        $columns = $table->getColumns();
        $this->assertCount(1, $columns);
        foreach ($columns as $column) {
            $this->assertInstanceOf('\Phoenix\Database\Element\Column', $column);
        }
        $idColumn = $table->getColumn('id');
        $this->assertInstanceOf('\Phoenix\Database\Element\Column', $idColumn);
        $this->assertEquals('id', $idColumn->getName());
        $this->assertEquals('integer', $idColumn->getType());
        $this->assertFalse($idColumn->allowNull());
        $this->assertNull($idColumn->getDefault());
        $this->assertTrue($idColumn->isSigned());
        $this->assertNull($idColumn->getLength());
        $this->assertNull($idColumn->getDecimals());
        $this->assertTrue($idColumn->isAutoincrement());
        $primaryColumns = $table->getPrimaryColumns();
        $this->assertCount(1, $primaryColumns);
        foreach ($primaryColumns as $primaryColumn) {
            $this->assertTrue(is_string($primaryColumn));
        }
    }
    
    public function testNoPrimaryKey()
    {
        $table = new Table('test');
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('title', 'string')));
        
        $columns = $table->getColumns();
        $this->assertCount(1, $columns);
        foreach ($columns as $column) {
            $this->assertInstanceOf('\Phoenix\Database\Element\Column', $column);
        }
        $this->assertCount(0, $table->getPrimaryColumns());
    }
    
    public function testStringPrimaryKey()
    {
        $table = new Table('test');
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('identifier', 'string')));
        $table->addPrimary('identifier');

        $columns = $table->getColumns();
        $this->assertCount(1, $columns);
        foreach ($columns as $column) {
            $this->assertInstanceOf('\Phoenix\Database\Element\Column', $column);
        }
        $this->assertCount(1, $table->getPrimaryColumns());
    }
    
    public function testMultiPrimaryKey()
    {
        $table = new Table('test');
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('identifier1', 'string')));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('identifier2', 'string')));
        $table->addPrimary(['identifier1', 'identifier2']);

        $columns = $table->getColumns();
        $this->assertCount(2, $columns);
        foreach ($columns as $column) {
            $this->assertInstanceOf('\Phoenix\Database\Element\Column', $column);
        }
        $this->assertCount(2, $table->getPrimaryColumns());
    }
    
    public function testDropPrimaryKey()
    {
        $table = new Table('test');
        $this->assertFalse($table->hasPrimaryKeyToDrop());
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->dropPrimaryKey());
        $this->assertTrue($table->hasPrimaryKeyToDrop());
    }
    
    public function testColumns()
    {
        $table = new Table('test');
        $table->addPrimary(true);
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('title', 'string')));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('total', 'int')));
        $this->assertCount(3, $table->getColumns());
        
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->changeColumn('title', new Column('new_title', 'string')));
        $this->assertCount(3, $table->getColumns());
        $this->assertCount(0, $table->getColumnsToChange());
        
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->changeColumn('alias', new Column('new_alias', 'string')));
        $this->assertCount(3, $table->getColumns());
        $this->assertCount(1, $table->getColumnsToChange());
        
        $this->assertCount(0, $table->getColumnsToDrop());
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->dropColumn('title'));
        $this->assertCount(3, $table->getColumns());
        $this->assertCount(1, $table->getColumnsToDrop());
    }
    
    public function testIndexes()
    {
        $table = new Table('test');
        $this->assertCount(0, $table->getIndexes());
        $table->addPrimary(true);
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addIndex(new Index('title', 'title', 'unique')));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addIndex(new Index(['title', 'alias'], 'title_alias')));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addIndex(new Index('bodytext', 'bodytext', 'fulltext')));
        $this->assertCount(3, $table->getIndexes());
        $this->assertCount(0, $table->getIndexesToDrop());
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->dropIndex('title_alias'));
        $this->assertCount(1, $table->getIndexesToDrop());
        $this->assertEquals('title_alias', $table->getIndexesToDrop()[0]);
    }
    
    public function testForeignKeys()
    {
        $table = new Table('test');
        $this->assertCount(0, $table->getForeignKeys());
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addForeignKey(new ForeignKey('first_column', 'foreign_table')));
        $this->assertCount(1, $table->getForeignKeys());
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addForeignKey(new ForeignKey('second_column', 'foreign_table')));
        $this->assertCount(2, $table->getForeignKeys());
        foreach ($table->getForeignKeys() as $foreignKey) {
            $this->assertInstanceOf('\Phoenix\Database\Element\ForeignKey', $foreignKey);
        }
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->dropForeignKey('first_column'));
        $this->assertCount(1, $table->getForeignKeysToDrop());
        $this->assertEquals('test_first_column', $table->getForeignKeysToDrop()[0]);
    }

    public function testGetters()
    {
        $table = new Table('test');
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('title', 'string')));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('total', 'int')));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('bodytext', 'text')));
        
        $columns = $table->getColumns();
        $this->assertCount(3, $columns);
        foreach ($columns as $column) {
            $this->assertInstanceOf('\Phoenix\Database\Element\Column', $column);
        }
        $this->assertInstanceOf('\Phoenix\Database\Element\Column', $table->getColumn('title'));
        
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addIndex(new Index('title', 'title', 'unique')));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addIndex(new Index(['title', 'alias'], 'title_alias')));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addIndex(new Index(['bodytext'], 'bodytext', 'fulltext')));
        
        $indexes = $table->getIndexes();
        $this->assertCount(3, $indexes);
        foreach ($indexes as $index) {
            $this->assertInstanceOf('\Phoenix\Database\Element\Index', $index);
        }
        
        $this->setExpectedException('\Exception', 'Column "unknown_column" not found');
        $table->getColumn('unknown_column');
    }    
}
