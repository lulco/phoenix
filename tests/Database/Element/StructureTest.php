<?php

declare(strict_types=1);

namespace Phoenix\Tests\Database\Element;

use Phoenix\Database\Element\Column;
use Phoenix\Database\Element\MigrationTable;
use Phoenix\Database\Element\Structure;
use Phoenix\Database\Element\Table;
use PHPUnit\Framework\TestCase;

final class StructureTest extends TestCase
{
    public function testEmpty(): void
    {
        $structure = new Structure();
        $this->assertEquals([], $structure->getTables());
        $this->assertNull($structure->getTable('some_table'));
    }

    public function testAddSimpleTable(): void
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
        $this->assertEquals('test', $table->getName());
        $this->assertCount(2, $table->getColumns());
        $idColumn = $table->getColumn('id');
        $this->assertInstanceOf(Column::class, $idColumn);
        $titleColumn = $table->getColumn('title');
        $this->assertInstanceOf(Column::class, $titleColumn);
    }
}
