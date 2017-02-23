<?php

namespace Phoenix\Tests\Database\Element;

use Exception;
use Phoenix\Database\Element\Column;
use Phoenix\Database\Element\ForeignKey;
use Phoenix\Database\Element\Index;
use Phoenix\Database\Element\MigrationTable;
use PHPUnit_Framework_TestCase;

class MigrationTableTest extends PHPUnit_Framework_TestCase
{
    public function testDefaultConstruct()
    {
        $table = new MigrationTable('test');
        $table->addPrimary(true);
        $this->assertEquals('test', $table->getName());
        $this->assertNull($table->getCharset());
        $this->assertNull($table->getCollation());
        $this->assertNull($table->getNewName());
        $this->assertEquals(MigrationTable::ACTION_CREATE, $table->getAction());

        $columns = $table->getColumns();
        $this->assertCount(1, $columns);
        foreach ($columns as $column) {
            $this->assertInstanceOf(Column::class, $column);
        }
        $idColumn = $table->getColumn('id');
        $this->assertInstanceOf(Column::class, $idColumn);
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
        $table = new MigrationTable('test');
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('title', 'string'));

        $columns = $table->getColumns();
        $this->assertCount(1, $columns);
        foreach ($columns as $column) {
            $this->assertInstanceOf(Column::class, $column);
        }
        $this->assertCount(0, $table->getPrimaryColumns());
    }

    public function testStringPrimaryKey()
    {
        $table = new MigrationTable('test');
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('identifier', 'string'));
        $table->addPrimary('identifier');

        $columns = $table->getColumns();
        $this->assertCount(1, $columns);
        foreach ($columns as $column) {
            $this->assertInstanceOf(Column::class, $column);
        }
        $this->assertCount(1, $table->getPrimaryColumns());
    }

    public function testMultiPrimaryKey()
    {
        $table = new MigrationTable('test');
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('identifier1', 'string'));
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('identifier2', 'string'));
        $table->addPrimary(['identifier1', 'identifier2']);

        $columns = $table->getColumns();
        $this->assertCount(2, $columns);
        foreach ($columns as $column) {
            $this->assertInstanceOf(Column::class, $column);
        }
        $this->assertCount(2, $table->getPrimaryColumns());
    }

    public function testDropPrimaryKey()
    {
        $table = new MigrationTable('test');
        $this->assertFalse($table->hasPrimaryKeyToDrop());
        $this->assertInstanceOf(MigrationTable::class, $table->dropPrimaryKey());
        $this->assertTrue($table->hasPrimaryKeyToDrop());
    }

    public function testColumns()
    {
        $table = new MigrationTable('test');
        $table->addPrimary(true);
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('title', 'string'));
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('total', 'int'));
        $this->assertCount(3, $table->getColumns());

        $this->assertInstanceOf(MigrationTable::class, $table->changeColumn('title', 'new_title', 'string'));
        $this->assertCount(3, $table->getColumns());
        $this->assertCount(0, $table->getColumnsToChange());

        $this->assertInstanceOf(MigrationTable::class, $table->changeColumn('alias', 'new_alias', 'string'));
        $this->assertCount(3, $table->getColumns());
        $this->assertCount(1, $table->getColumnsToChange());

        $this->assertCount(0, $table->getColumnsToDrop());
        $this->assertInstanceOf(MigrationTable::class, $table->dropColumn('title'));
        $this->assertCount(3, $table->getColumns());
        $this->assertCount(1, $table->getColumnsToDrop());
    }

    public function testIndexes()
    {
        $table = new MigrationTable('test');
        $this->assertCount(0, $table->getIndexes());
        $table->addPrimary(true);
        $this->assertInstanceOf(MigrationTable::class, $table->addIndex('title', 'unique'));
        $this->assertInstanceOf(MigrationTable::class, $table->addIndex(['title', 'alias'], '', '', 'index_name'));
        $this->assertInstanceOf(MigrationTable::class, $table->addIndex('bodytext', 'fulltext'));
        $this->assertCount(3, $table->getIndexes());
        $this->assertEquals('idx_test_title', $table->getIndexes()[0]->getName());
        $this->assertEquals('index_name', $table->getIndexes()[1]->getName());
        $this->assertEquals('idx_test_bodytext', $table->getIndexes()[2]->getName());

        $this->assertCount(0, $table->getIndexesToDrop());
        $this->assertInstanceOf(MigrationTable::class, $table->dropIndex('title_alias'));
        $this->assertInstanceOf(MigrationTable::class, $table->dropIndexByName('title_alias'));
        $this->assertCount(2, $table->getIndexesToDrop());
        $this->assertEquals('idx_test_title_alias', $table->getIndexesToDrop()[0]);
        $this->assertEquals('title_alias', $table->getIndexesToDrop()[1]);
    }

    public function testForeignKeys()
    {
        $table = new MigrationTable('test');
        $this->assertCount(0, $table->getForeignKeys());
        $this->assertInstanceOf(MigrationTable::class, $table->addForeignKey('first_column', 'foreign_table'));
        $this->assertCount(1, $table->getForeignKeys());
        $this->assertInstanceOf(MigrationTable::class, $table->addForeignKey('second_column', 'foreign_table'));
        $this->assertCount(2, $table->getForeignKeys());
        foreach ($table->getForeignKeys() as $foreignKey) {
            $this->assertInstanceOf(ForeignKey::class, $foreignKey);
        }
        $this->assertInstanceOf(MigrationTable::class, $table->dropForeignKey('first_column'));
        $this->assertCount(1, $table->getForeignKeysToDrop());
        $this->assertEquals('test_first_column', $table->getForeignKeysToDrop()[0]);
    }

    public function testGetters()
    {
        $table = new MigrationTable('test');
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('title', 'string'));
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('total', 'int'));
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('bodytext', 'text'));

        $columns = $table->getColumns();
        $this->assertCount(3, $columns);
        foreach ($columns as $column) {
            $this->assertInstanceOf(Column::class, $column);
        }
        $this->assertInstanceOf(Column::class, $table->getColumn('title'));

        $this->assertInstanceOf(MigrationTable::class, $table->addIndex('title', 'unique'));
        $this->assertInstanceOf(MigrationTable::class, $table->addIndex(['title', 'alias']));
        $this->assertInstanceOf(MigrationTable::class, $table->addIndex(['bodytext'], 'fulltext'));

        $indexes = $table->getIndexes();
        $this->assertCount(3, $indexes);
        foreach ($indexes as $index) {
            $this->assertInstanceOf(Index::class, $index);
        }

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Column "unknown_column" not found');
        $table->getColumn('unknown_column');
    }

    public function testCharsetAndCollation()
    {
        $table = new MigrationTable('test');
        $this->assertInstanceOf(MigrationTable::class, $table->setCharset('my_charset'));
        $this->assertInstanceOf(MigrationTable::class, $table->setCollation('my_collation'));

        $this->assertEquals('my_charset', $table->getCharset());
        $this->assertEquals('my_collation', $table->getCollation());
    }

    public function testCreate()
    {
        $table = new MigrationTable('test');
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('title', 'string'));
        $this->assertCount(0, $table->getPrimaryColumns());

        $this->assertNull($table->create());

        $this->assertEquals('test', $table->getName());
        $this->assertNull($table->getCharset());
        $this->assertNull($table->getCollation());
        $this->assertNull($table->getNewName());
        $this->assertEquals(MigrationTable::ACTION_CREATE, $table->getAction());

        $columns = $table->getColumns();
        $this->assertCount(2, $columns);
        $idColumn = $table->getColumn('id');
        $this->assertInstanceOf(Column::class, $idColumn);
        $this->assertEquals('id', $idColumn->getName());
        $this->assertEquals('integer', $idColumn->getType());
        $this->assertFalse($idColumn->allowNull());
        $this->assertNull($idColumn->getDefault());
        $this->assertTrue($idColumn->isSigned());
        $this->assertNull($idColumn->getLength());
        $this->assertNull($idColumn->getDecimals());
        $this->assertTrue($idColumn->isAutoincrement());
        $this->assertCount(1, $table->getPrimaryColumns());
    }

    public function testSave()
    {
        $table = new MigrationTable('test');
        $this->assertEquals('test', $table->getName());
        $this->assertEquals(MigrationTable::ACTION_CREATE, $table->getAction());
        $this->assertNull($table->save());
        $this->assertEquals('test', $table->getName());
        $this->assertEquals(MigrationTable::ACTION_ALTER, $table->getAction());
    }

    public function testRename()
    {
        $table = new MigrationTable('test');
        $this->assertEquals('test', $table->getName());
        $this->assertNull($table->getNewName());
        $this->assertNull($table->rename('new_test'));
        $this->assertEquals(MigrationTable::ACTION_RENAME, $table->getAction());
        $this->assertEquals('test', $table->getName());
        $this->assertEquals('new_test', $table->getNewName());
    }

    public function testDrop()
    {
        $table = new MigrationTable('test');
        $this->assertEquals('test', $table->getName());
        $this->assertEquals(MigrationTable::ACTION_CREATE, $table->getAction());
        $this->assertNull($table->drop());
        $this->assertEquals('test', $table->getName());
        $this->assertEquals(MigrationTable::ACTION_DROP, $table->getAction());
    }
}
