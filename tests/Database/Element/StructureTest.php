<?php

namespace Phoenix\Tests\Database\Element;

use Phoenix\Database\Element\Column;
use Phoenix\Database\Element\MigrationTable;
use Phoenix\Database\Element\Structure;
use Phoenix\Database\Element\Table;
use PHPUnit_Framework_TestCase;

class StructureTest extends PHPUnit_Framework_TestCase
{
    public function testEmpty()
    {
        $structure = new Structure();
        $this->assertEquals([], $structure->getTables());
        $this->assertNull($structure->getTable('some_table'));
    }

    public function testPrepare()
    {

    }

    public function testAddRenameAndDropSimpleTable()
    {
        $structure = new Structure();

        $this->assertCount(0, $structure->getTables());
        $this->assertEquals([], $structure->getTables());
        $this->assertNull($structure->getTable('test'));

        $migrationTable = new MigrationTable('test');
        $migrationTable->addColumn('title', 'string');
        $migrationTable->create();
        $this->assertInstanceOf(Structure::class, $structure->update($migrationTable));
        $this->assertCount(1, $structure->getTables());
        $table = $structure->getTable('test');
        $this->assertInstanceOf(Table::class, $table);
        $this->assertCount(2, $table->getColumns());
        $idColumn = $table->getColumn('id');
        $this->assertInstanceOf(Column::class, $idColumn);
        $titleColumn = $table->getColumn('title');
        $this->assertInstanceOf(Column::class, $titleColumn);


        $migrationTableToRename = new MigrationTable('test');
        $migrationTableToRename->rename('test_2');
        $structure->update($migrationTableToRename);
        $this->assertCount(1, $structure->getTables());
        $this->assertNull($structure->getTable('test'));
        $table2 = $structure->getTable('test_2');
        $this->assertInstanceOf(Table::class, $table2);
        $this->assertCount(2, $table2->getColumns());
        $idColumn = $table2->getColumn('id');
        $this->assertInstanceOf(Column::class, $idColumn);
        $titleColumn = $table2->getColumn('title');
        $this->assertInstanceOf(Column::class, $titleColumn);


        $migrationTableToDrop = new MigrationTable('test_2');
        $migrationTableToDrop->drop();
        $structure->update($migrationTableToDrop);
        $this->assertCount(0, $structure->getTables());
        $this->assertEquals([], $structure->getTables());
        $this->assertNull($structure->getTable('test_2'));
    }
}
