<?php

declare(strict_types=1);

namespace Phoenix\Tests\Database\Element;

use InvalidArgumentException;
use Phoenix\Database\Element\Column;
use Phoenix\Database\Element\ForeignKey;
use Phoenix\Database\Element\Index;
use Phoenix\Database\Element\MigrationTable;
use Phoenix\Database\Element\Table;
use Phoenix\Exception\InvalidArgumentValueException;
use PHPUnit\Framework\TestCase;

final class MigrationTableTest extends TestCase
{
    public function testDefaultConstruct(): void
    {
        $table = new MigrationTable('test');
        $table->addPrimary(true);
        $this->assertEquals('test', $table->getName());
        $this->assertNull($table->getCharset());
        $this->assertNull($table->getCollation());
        $this->assertNull($table->getComment());
        $this->assertNull($table->getNewName());
        $this->assertEquals(MigrationTable::ACTION_ALTER, $table->getAction());

        $columns = $table->getColumns();
        $this->assertCount(1, $columns);
        foreach ($columns as $column) {
            $this->assertInstanceOf(Column::class, $column);
        }
        $idColumn = $table->getColumn('id');
        $this->assertInstanceOf(Column::class, $idColumn);
        $this->assertEquals('id', $idColumn->getName());
        $this->assertEquals('integer', $idColumn->getType());
        $this->assertFalse($idColumn->getSettings()->allowNull());
        $this->assertNull($idColumn->getSettings()->getDefault());
        $this->assertTrue($idColumn->getSettings()->isSigned());
        $this->assertNull($idColumn->getSettings()->getLength());
        $this->assertNull($idColumn->getSettings()->getDecimals());
        $this->assertTrue($idColumn->getSettings()->isAutoincrement());
        $primaryColumnNames = $table->getPrimaryColumnNames();
        $this->assertCount(1, $primaryColumnNames);
        foreach ($primaryColumnNames as $primaryColumn) {
            $this->assertTrue(is_string($primaryColumn));
        }
    }

    public function testNoPrimaryKey(): void
    {
        $table = new MigrationTable('test', false);
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('title', 'string'));
        $table->create();

        $columns = $table->getColumns();
        $this->assertCount(1, $columns);
        foreach ($columns as $column) {
            $this->assertInstanceOf(Column::class, $column);
        }
        $this->assertCount(0, $table->getPrimaryColumnNames());
    }

    public function testStringPrimaryKey(): void
    {
        $table = new MigrationTable('test', 'identifier');
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('identifier', 'string'));
        $table->create();

        $columns = $table->getColumns();
        $this->assertCount(1, $columns);
        foreach ($columns as $column) {
            $this->assertInstanceOf(Column::class, $column);
        }
        $this->assertCount(1, $table->getPrimaryColumnNames());
    }

    public function testMultiPrimaryKey(): void
    {
        $table = new MigrationTable('test', ['identifier1', 'identifier2']);
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('identifier1', 'string'));
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('identifier2', 'string'));
        $table->create();

        $columns = $table->getColumns();
        $this->assertCount(2, $columns);
        foreach ($columns as $column) {
            $this->assertInstanceOf(Column::class, $column);
        }
        $this->assertCount(2, $table->getPrimaryColumnNames());
    }

    public function testDropPrimaryKey(): void
    {
        $table = new MigrationTable('test');
        $this->assertFalse($table->hasPrimaryKeyToDrop());
        $this->assertInstanceOf(MigrationTable::class, $table->dropPrimaryKey());
        $this->assertTrue($table->hasPrimaryKeyToDrop());
    }

    public function testColumns(): void
    {
        $table = new MigrationTable('test');
        $table->addPrimary(true);
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('title', 'string'));
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('total', 'integer'));
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

    public function testUnsupportedColumnType(): void
    {
        $table = new MigrationTable('unsupported');
        $this->expectException(InvalidArgumentValueException::class);
        $this->expectExceptionMessage('Type "unsupported" is not allowed');
        $table->addColumn('title', 'unsupported');
    }

    public function testIndexes(): void
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

    public function testForeignKeys(): void
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
        $this->assertEquals('first_column', $table->getForeignKeysToDrop()[0]);
    }

    public function testGetters(): void
    {
        $table = new MigrationTable('test');
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('title', 'string'));
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('total', 'integer'));
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
        $this->assertNull($table->getColumn('unknown_column'));
    }

    public function testCharsetCollationAndComment(): void
    {
        $table = new MigrationTable('test');
        $this->assertInstanceOf(MigrationTable::class, $table->setCharset('my_charset'));
        $this->assertInstanceOf(MigrationTable::class, $table->setCollation('my_collation'));
        $this->assertInstanceOf(MigrationTable::class, $table->setComment('my_comment'));

        $this->assertEquals('my_charset', $table->getCharset());
        $this->assertEquals('my_collation', $table->getCollation());
        $this->assertEquals('my_comment', $table->getComment());

        $this->assertInstanceOf(MigrationTable::class, $table->unsetComment());
        $this->assertEquals('', $table->getComment());
    }

    public function testCreate(): void
    {
        $table = new MigrationTable('test');
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('title', 'string'));
        $this->assertCount(0, $table->getPrimaryColumnNames());
        $table->create();

        $this->assertEquals('test', $table->getName());
        $this->assertNull($table->getCharset());
        $this->assertNull($table->getCollation());
        $this->assertNull($table->getComment());
        $this->assertNull($table->getNewName());
        $this->assertEquals(MigrationTable::ACTION_CREATE, $table->getAction());

        $columns = $table->getColumns();
        $this->assertCount(2, $columns);
        $idColumn = $table->getColumn('id');
        $this->assertInstanceOf(Column::class, $idColumn);
        $this->assertEquals('id', $idColumn->getName());
        $this->assertEquals('integer', $idColumn->getType());
        $this->assertFalse($idColumn->getSettings()->allowNull());
        $this->assertNull($idColumn->getSettings()->getDefault());
        $this->assertTrue($idColumn->getSettings()->isSigned());
        $this->assertNull($idColumn->getSettings()->getLength());
        $this->assertNull($idColumn->getSettings()->getDecimals());
        $this->assertTrue($idColumn->getSettings()->isAutoincrement());
        $this->assertCount(1, $table->getPrimaryColumnNames());
    }

    public function testSave(): void
    {
        $table = new MigrationTable('test');
        $this->assertEquals('test', $table->getName());
        $this->assertEquals(MigrationTable::ACTION_ALTER, $table->getAction());
        $table->create();
        $this->assertEquals('test', $table->getName());
        $this->assertEquals(MigrationTable::ACTION_CREATE, $table->getAction());
    }

    public function testRename(): void
    {
        $table = new MigrationTable('test');
        $this->assertEquals('test', $table->getName());
        $this->assertNull($table->getNewName());
        $table->rename('new_test');
        $this->assertEquals(MigrationTable::ACTION_RENAME, $table->getAction());
        $this->assertEquals('test', $table->getName());
        $this->assertEquals('new_test', $table->getNewName());
    }

    public function testDrop(): void
    {
        $table = new MigrationTable('test');
        $this->assertEquals('test', $table->getName());
        $this->assertEquals(MigrationTable::ACTION_ALTER, $table->getAction());
        $table->drop();
        $this->assertEquals('test', $table->getName());
        $this->assertEquals(MigrationTable::ACTION_DROP, $table->getAction());
    }

    public function testCopy(): void
    {
        $table = new MigrationTable('test');
        $this->assertEquals('test', $table->getName());
        $this->assertNull($table->getNewName());
        $table->copy('new_test');
        $this->assertEquals(MigrationTable::ACTION_COPY, $table->getAction());
        $this->assertEquals('test', $table->getName());
        $this->assertEquals('new_test', $table->getNewName());
        $this->assertEquals(MigrationTable::COPY_ONLY_STRUCTURE, $table->getCopyType());

        $table->copy('new_test', MigrationTable::COPY_ONLY_STRUCTURE);
        $this->assertEquals(MigrationTable::ACTION_COPY, $table->getAction());
        $this->assertEquals('test', $table->getName());
        $this->assertEquals('new_test', $table->getNewName());
        $this->assertEquals(MigrationTable::COPY_ONLY_STRUCTURE, $table->getCopyType());

        $table->copy('new_test', MigrationTable::COPY_ONLY_DATA);
        $this->assertEquals(MigrationTable::ACTION_COPY, $table->getAction());
        $this->assertEquals('test', $table->getName());
        $this->assertEquals('new_test', $table->getNewName());
        $this->assertEquals(MigrationTable::COPY_ONLY_DATA, $table->getCopyType());

        $table->copy('new_test', MigrationTable::COPY_STRUCTURE_AND_DATA);
        $this->assertEquals(MigrationTable::ACTION_COPY, $table->getAction());
        $this->assertEquals('test', $table->getName());
        $this->assertEquals('new_test', $table->getNewName());
        $this->assertEquals(MigrationTable::COPY_STRUCTURE_AND_DATA, $table->getCopyType());

        $this->expectException(InvalidArgumentValueException::class);
        $this->expectExceptionMessage('Copy type "unknown_copy_type" is not allowed');
        $table->copy('new_test', 'unknown_copy_type');
    }

    public function testToTable(): void
    {
        $migrationTable = new MigrationTable('test');
        $migrationTable->setCharset('my_charset');
        $migrationTable->setCollation('my_collation');
        $migrationTable->setComment('my_comment');
        $migrationTable->addColumn('title', 'string');
        $migrationTable->addColumn('alias', 'string');
        $migrationTable->addColumn('fk_table1_id', 'integer');
        $migrationTable->addColumn('sku', 'string');
        $migrationTable->addColumn('name', 'string');
        $migrationTable->addColumn('option', 'string');
        $migrationTable->addIndex('alias', Index::TYPE_UNIQUE);
        $migrationTable->addForeignKey('fk_table1_id', 'table1');
        $migrationTable->addUniqueConstraint('sku', 'u_sku');
        $migrationTable->addUniqueConstraint(['name', 'option'], 'u_name_option');
        $migrationTable->create();
        $table = $migrationTable->toTable();
        $this->assertCount(7, $migrationTable->getColumns());
        $this->assertCount(1, $migrationTable->getIndexes());
        $this->assertCount(1, $migrationTable->getForeignKeys());
        $this->assertCount(2, $migrationTable->getUniqueConstraints());
        $this->assertInstanceOf(Table::class, $table);
        $this->assertEquals($migrationTable->getName(), $table->getName());
        $this->assertEquals($migrationTable->getCharset(), $table->getCharset());
        $this->assertEquals($migrationTable->getCollation(), $table->getCollation());
        $this->assertEquals($migrationTable->getComment(), $table->getComment());
        $this->assertEquals($migrationTable->getPrimaryColumnNames(), $table->getPrimary());
        $this->assertCount(count($migrationTable->getColumns()), $table->getColumns());
        $this->assertCount(count($migrationTable->getIndexes()), $table->getIndexes());
        $this->assertCount(count($migrationTable->getForeignKeys()), $table->getForeignKeys());
        $this->assertCount(count($migrationTable->getUniqueConstraints()), $table->getUniqueConstraints());
        $this->assertEquals($migrationTable->getColumn('id'), $table->getColumn('id'));
        $this->assertEquals($migrationTable->getColumn('title'), $table->getColumn('title'));
        $this->assertEquals($migrationTable->getColumn('alias'), $table->getColumn('alias'));
        $this->assertEquals($migrationTable->getColumn('fk_table1_id'), $table->getColumn('fk_table1_id'));
        $this->assertEquals($migrationTable->getColumn('sku'), $table->getColumn('sku'));
        $this->assertEquals($migrationTable->getColumn('name'), $table->getColumn('name'));
        $this->assertEquals($migrationTable->getColumn('option'), $table->getColumn('option'));
    }

    public function testTryingToAddPrimaryColumnAsStrings(): void
    {
        $table = new MigrationTable('add_primary_columns');
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('All primaryColumns have to be instance of "' . Column::class . '"');
        $table->addPrimaryColumns(['id']);
    }
}
