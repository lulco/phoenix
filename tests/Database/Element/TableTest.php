<?php

namespace Phoenix\Tests\Database\Element;

use Phoenix\Database\Element\Column;
use Phoenix\Database\Element\ForeignKey;
use Phoenix\Database\Element\Index;
use Phoenix\Database\Element\Table;
use PHPUnit_Framework_TestCase;

class TableTest extends PHPUnit_Framework_TestCase
{
    public function testSimpleTable()
    {
        $table = new Table('test');
        $this->assertInstanceOf(Table::class, $table->addColumn(new Column('title', 'string')));
        $this->assertInstanceOf(Table::class, $table->addColumn(new Column('alias', 'string')));
        $this->assertInstanceOf(Table::class, $table->addColumn(new Column('bodytext', 'text')));
        $this->assertInstanceOf(Table::class, $table->addColumn(new Column('fk_table1_id', 'integer')));

        $this->assertEquals('test', $table->getName());
        $this->assertNull($table->getPrimary());
        $this->assertNull($table->getCharset());
        $this->assertNull($table->getCollation());
        $this->assertCount(4, $table->getColumns());
        $this->assertInstanceOf(Column::class, $table->getColumn('title'));
        $this->assertInstanceOf(Column::class, $table->getColumn('alias'));
        $this->assertInstanceOf(Column::class, $table->getColumn('bodytext'));
        $this->assertInstanceOf(Column::class, $table->getColumn('fk_table1_id'));
        $this->assertCount(0, $table->getIndexes());
        $this->assertCount(0, $table->getForeignKeys());
    }

    public function testComplexTable()
    {
        $table = new Table('test');
        $this->assertInstanceOf(Table::class, $table->setCharset('my_charset'));
        $this->assertInstanceOf(Table::class, $table->setCollation('my_collation'));
        $this->assertInstanceOf(Table::class, $table->addColumn(new Column('id', 'integer')));
        $this->assertInstanceOf(Table::class, $table->addColumn(new Column('title', 'string')));
        $this->assertInstanceOf(Table::class, $table->addColumn(new Column('alias', 'string')));
        $this->assertInstanceOf(Table::class, $table->addColumn(new Column('bodytext', 'text')));
        $this->assertInstanceOf(Table::class, $table->addColumn(new Column('fk_table1_id', 'integer')));
        $this->assertInstanceOf(Table::class, $table->setPrimary(['id']));
        $this->assertInstanceOf(Table::class, $table->addIndex(new Index('alias', Index::TYPE_UNIQUE)));
        $this->assertInstanceOf(Table::class, $table->addForeignKey(new ForeignKey('fk_table1_id', 'table1')));

        $this->assertEquals('test', $table->getName());
        $this->assertEquals(['id'], $table->getPrimary());
        $this->assertEquals('my_charset', $table->getCharset());
        $this->assertEquals('my_collation', $table->getCollation());
        $this->assertCount(5, $table->getColumns());
        $this->assertInstanceOf(Column::class, $table->getColumn('id'));
        $this->assertInstanceOf(Column::class, $table->getColumn('title'));
        $this->assertInstanceOf(Column::class, $table->getColumn('alias'));
        $this->assertInstanceOf(Column::class, $table->getColumn('bodytext'));
        $this->assertInstanceOf(Column::class, $table->getColumn('fk_table1_id'));
        $this->assertCount(1, $table->getIndexes());
        $this->assertCount(1, $table->getForeignKeys());
    }

    public function testChangeTable()
    {
        $table = new Table('test');
        $this->assertInstanceOf(Table::class, $table->setCharset('my_charset'));
        $this->assertInstanceOf(Table::class, $table->setCollation('my_collation'));
        $this->assertInstanceOf(Table::class, $table->addColumn(new Column('id', 'integer')));
        $this->assertInstanceOf(Table::class, $table->addColumn(new Column('title', 'string')));
        $this->assertInstanceOf(Table::class, $table->addColumn(new Column('alias', 'string')));
        $this->assertInstanceOf(Table::class, $table->addColumn(new Column('bodytext', 'text')));
        $this->assertInstanceOf(Table::class, $table->addColumn(new Column('fk_table1_id', 'integer')));
        $this->assertInstanceOf(Table::class, $table->setPrimary(['id']));
        $this->assertInstanceOf(Table::class, $table->addIndex(new Index('alias', 'alias_index', Index::TYPE_UNIQUE)));
        $this->assertInstanceOf(Table::class, $table->addForeignKey(new ForeignKey('fk_table1_id', 'table1')));

        $this->assertEquals('test', $table->getName());
        $this->assertEquals(['id'], $table->getPrimary());
        $this->assertEquals('my_charset', $table->getCharset());
        $this->assertEquals('my_collation', $table->getCollation());
        $this->assertCount(5, $table->getColumns());
        $this->assertInstanceOf(Column::class, $table->getColumn('id'));
        $this->assertInstanceOf(Column::class, $table->getColumn('title'));
        $this->assertInstanceOf(Column::class, $table->getColumn('alias'));
        $this->assertInstanceOf(Column::class, $table->getColumn('bodytext'));
        $this->assertInstanceOf(Column::class, $table->getColumn('fk_table1_id'));
        $this->assertCount(1, $table->getIndexes());
        $this->assertCount(1, $table->getForeignKeys());

        $this->assertInstanceOf(Table::class, $table->removeColumn('bodytext'));
        $this->assertCount(4, $table->getColumns());

        $this->assertInstanceOf(Table::class, $table->changeColumn('title', new Column('new_title', 'string')));
        $this->assertCount(4, $table->getColumns());
        $this->assertInstanceOf(Column::class, $table->getColumn('new_title'));

        $this->assertInstanceOf(Table::class, $table->removeIndex('alias_index'));
        $this->assertCount(0, $table->getIndexes());

        $this->assertInstanceOf(Table::class, $table->removeForeignKey('fk_table1_id'));
        $this->assertCount(0, $table->getForeignKeys());
    }

    public function testGetColumn()
    {
        $table = new Table('test');
        $this->assertInstanceOf(Table::class, $table->addColumn(new Column('title', 'string')));
        $this->assertInstanceOf(Table::class, $table->addColumn(new Column('alias', 'string')));
        $this->assertInstanceOf(Table::class, $table->addColumn(new Column('bodytext', 'text')));
        $this->assertInstanceOf(Table::class, $table->addColumn(new Column('fk_table1_id', 'integer')));

        $this->assertEquals('test', $table->getName());
        $this->assertInstanceOf(Column::class, $table->getColumn('title'));
        $this->assertInstanceOf(Column::class, $table->getColumn('alias'));
        $this->assertInstanceOf(Column::class, $table->getColumn('bodytext'));
        $this->assertInstanceOf(Column::class, $table->getColumn('fk_table1_id'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Column "unknown_column" not found');
        $table->getColumn('unknown_column');
    }

}
