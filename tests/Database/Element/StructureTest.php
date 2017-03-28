<?php

namespace Phoenix\Tests\Database\Element;

use Phoenix\Database\Element\Column;
use Phoenix\Database\Element\ForeignKey;
use Phoenix\Database\Element\Index;
use Phoenix\Database\Element\MigrationTable;
use Phoenix\Database\Element\Structure;
use Phoenix\Database\Element\Table;
use Phoenix\Exception\StructureException;
use PHPUnit_Framework_TestCase;

class StructureTest extends PHPUnit_Framework_TestCase
{
    public function testEmpty()
    {
        $structure = new Structure();
        $this->assertEquals([], $structure->getTables());
        $this->assertFalse($structure->tableExists('some_table'));
        $this->assertNull($structure->getTable('some_table'));
    }

    public function testAddRenameAndDropSimpleTable()
    {
        $structure = new Structure();

        $this->assertCount(0, $structure->getTables());
        $this->assertEquals([], $structure->getTables());
        $this->assertFalse($structure->tableExists('test'));
        $this->assertNull($structure->getTable('test'));


        $migrationTable = new MigrationTable('test');
        $migrationTable->addColumn('title', 'string');
        $migrationTable->create();
        $this->assertInstanceOf(Structure::class, $structure->update($migrationTable));
        $this->assertCount(1, $structure->getTables());
        $this->assertTrue($structure->tableExists('test'));
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
        $this->assertFalse($structure->tableExists('test'));
        $this->assertNull($structure->getTable('test'));
        $this->assertTrue($structure->tableExists('test_2'));
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
        $this->assertFalse($structure->tableExists('test'));
        $this->assertNull($structure->getTable('test'));
        $this->assertFalse($structure->tableExists('test_2'));
        $this->assertNull($structure->getTable('test_2'));
    }

    public function testPrepareAddNonExistingTable()
    {
        $structure = new Structure();

        $this->assertCount(0, $structure->getTables());
        $this->assertEquals([], $structure->getTables());
        $this->assertFalse($structure->tableExists('test'));
        $this->assertNull($structure->getTable('test'));

        $migrationTable = new MigrationTable('test');
        $migrationTable->addColumn('title', 'string');
        $migrationTable->create();

        $preparedMigrationTable = $structure->prepare($migrationTable);
        $this->assertInstanceOf(MigrationTable::class, $preparedMigrationTable);
        $this->assertEquals($preparedMigrationTable, $migrationTable);
    }

    public function testPrepareAddExistingTable()
    {
        $structure = $this->prepareSimpleStructure();
        $this->assertTrue($structure->tableExists('test'));

        $migrationExistingTable = new MigrationTable('test');
        $migrationExistingTable->addColumn('title', 'string');
        $migrationExistingTable->create();

        $this->expectException(StructureException::class);
        $this->expectExceptionMessage('Table "test" already exists');
        $structure->prepare($migrationExistingTable);
    }

    public function testPrepareDropNonExistingTable()
    {
        $structure = new Structure();

        $this->assertFalse($structure->tableExists('test'));
        $migrationNonExistingTable = new MigrationTable('test');
        $migrationNonExistingTable->addColumn('title', 'string');
        $migrationNonExistingTable->drop();

        $this->expectException(StructureException::class);
        $this->expectExceptionMessage('Table "test" doesn\'t exist');
        $structure->prepare($migrationNonExistingTable);
    }

    public function testPrepareAlterNonExistingTable()
    {
        $structure = new Structure();

        $this->assertFalse($structure->tableExists('test'));
        $migrationNonExistingTable = new MigrationTable('test');
        $migrationNonExistingTable->addColumn('title', 'string');
        $migrationNonExistingTable->save();

        $this->expectException(StructureException::class);
        $this->expectExceptionMessage('Table "test" doesn\'t exist');
        $structure->prepare($migrationNonExistingTable);
    }

    public function testPrepareRenameNonExistingTable()
    {
        $structure = new Structure();

        $this->assertFalse($structure->tableExists('test'));
        $migrationNonExistingTable = new MigrationTable('test');
        $migrationNonExistingTable->addColumn('title', 'string');
        $migrationNonExistingTable->rename('test_2');

        $this->expectException(StructureException::class);
        $this->expectExceptionMessage('Table "test" doesn\'t exist');
        $structure->prepare($migrationNonExistingTable);
    }

    public function testPrepareRenameToExistingTable()
    {
        $structure = $this->prepareSimpleStructure();

        $migrationTable = new MigrationTable('test_2');
        $migrationTable->addColumn('title', 'string');
        $migrationTable->create();
        $structure->update($migrationTable);

        $this->assertTrue($structure->tableExists('test'));
        $this->assertTrue($structure->tableExists('test_2'));
        $migrationTableToRename = new MigrationTable('test');
        $migrationTableToRename->addColumn('title', 'string');
        $migrationTableToRename->rename('test_2');

        $this->expectException(StructureException::class);
        $this->expectExceptionMessage('Table "test_2" already exists');
        $structure->prepare($migrationTableToRename);
    }

    public function testPrepareAddNonExistingColumn()
    {
        $structure = $this->prepareSimpleStructure();

        $this->assertTrue($structure->tableExists('test'));
        $testTable = $structure->getTable('test');
        $this->assertInstanceOf(Table::class, $testTable);
        $this->assertNull($testTable->getColumn('description'));

        $alterTable = new MigrationTable('test');
        $alterTable->addColumn('description', 'text');
        $alterTable->save();
        $preparedAlterTable = $structure->prepare($alterTable);
        $this->assertInstanceOf(MigrationTable::class, $preparedAlterTable);
        $this->assertEquals($preparedAlterTable, $alterTable);
    }

    public function testPrepareAddExistingColumn()
    {
        $structure = $this->prepareSimpleStructure();

        $this->assertTrue($structure->tableExists('test'));
        $testTable = $structure->getTable('test');
        $this->assertInstanceOf(Table::class, $testTable);
        $this->assertInstanceOf(Column::class, $testTable->getColumn('title'));

        $alterTable = new MigrationTable('test');
        $alterTable->addColumn('title', 'text');
        $alterTable->save();

        $this->expectException(StructureException::class);
        $this->expectExceptionMessage('Column "title" already exists in table "test"');
        $structure->prepare($alterTable);
    }

    public function testPrepareAlterExistingColumn()
    {
        $structure = $this->prepareSimpleStructure();

        $this->assertTrue($structure->tableExists('test'));
        $testTable = $structure->getTable('test');
        $this->assertInstanceOf(Table::class, $testTable);
        $this->assertInstanceOf(Column::class, $testTable->getColumn('title'));

        $migrationTable = new MigrationTable('test');
        $migrationTable->changeColumn('title', 'title', 'text');
        $migrationTable->save();

        $preparedMigrationTable = $structure->prepare($migrationTable);
        $this->assertInstanceOf(MigrationTable::class, $preparedMigrationTable);
        $this->assertEquals($preparedMigrationTable, $migrationTable);
    }

    public function testPrepareAlterNonExistingColumn()
    {
        $structure = $this->prepareSimpleStructure();

        $this->assertTrue($structure->tableExists('test'));
        $testTable = $structure->getTable('test');
        $this->assertInstanceOf(Table::class, $testTable);
        $this->assertNull($testTable->getColumn('description'));

        $migrationTable = new MigrationTable('test');
        $migrationTable->changeColumn('description', 'description', 'text');
        $migrationTable->save();

        $this->expectException(StructureException::class);
        $this->expectExceptionMessage('Column "description" doesn\'t exist in table "test"');
        $structure->prepare($migrationTable);
    }

    public function testPrepareRenameExistingColumn()
    {
        $structure = $this->prepareSimpleStructure();

        $this->assertTrue($structure->tableExists('test'));
        $testTable = $structure->getTable('test');
        $this->assertInstanceOf(Table::class, $testTable);
        $this->assertInstanceOf(Column::class, $testTable->getColumn('title'));

        $migrationTable = new MigrationTable('test');
        $migrationTable->changeColumn('title', 'new_title', 'text');
        $migrationTable->save();

        $preparedMigrationTable = $structure->prepare($migrationTable);
        $this->assertInstanceOf(MigrationTable::class, $preparedMigrationTable);
        $this->assertEquals($preparedMigrationTable, $migrationTable);
    }

    public function testPrepareRenameNonExistingColumn()
    {
        $structure = $this->prepareSimpleStructure();

        $this->assertTrue($structure->tableExists('test'));
        $testTable = $structure->getTable('test');
        $this->assertInstanceOf(Table::class, $testTable);
        $this->assertNull($testTable->getColumn('description'));

        $migrationTable = new MigrationTable('test');
        $migrationTable->changeColumn('description', 'new_description', 'text');
        $migrationTable->save();

        $this->expectException(StructureException::class);
        $this->expectExceptionMessage('Column "description" doesn\'t exist in table "test"');
        $structure->prepare($migrationTable);
    }

    public function testPrepareRenameColumnToExistingColumn()
    {
        $structure = $this->prepareSimpleStructure();

        $this->assertTrue($structure->tableExists('test'));
        $testTable = $structure->getTable('test');
        $this->assertInstanceOf(Table::class, $testTable);
        $this->assertInstanceOf(Column::class, $testTable->getColumn('title'));
        $this->assertInstanceOf(Column::class, $testTable->getColumn('alias'));

        $migrationTable = new MigrationTable('test');
        $migrationTable->changeColumn('title', 'alias', 'string');
        $migrationTable->save();

        $this->expectException(StructureException::class);
        $this->expectExceptionMessage('Column "alias" already exists in table "test"');
        $structure->prepare($migrationTable);
    }

    public function testPrepareDropExistingColumn()
    {
        $structure = $this->prepareSimpleStructure();

        $this->assertTrue($structure->tableExists('test'));
        $testTable = $structure->getTable('test');
        $this->assertInstanceOf(Table::class, $testTable);
        $this->assertInstanceOf(Column::class, $testTable->getColumn('alias'));

        $migrationTable = new MigrationTable('test');
        $migrationTable->dropColumn('alias');
        $migrationTable->save();

        $preparedMigrationTable = $structure->prepare($migrationTable);
        $this->assertInstanceOf(MigrationTable::class, $preparedMigrationTable);
        $this->assertEquals($preparedMigrationTable, $migrationTable);
    }

    public function testPrepareDropNonExistingColumn()
    {
        $structure = $this->prepareSimpleStructure();

        $this->assertTrue($structure->tableExists('test'));
        $testTable = $structure->getTable('test');
        $this->assertInstanceOf(Table::class, $testTable);
        $this->assertNull($testTable->getColumn('description'));

        $migrationTable = new MigrationTable('test');
        $migrationTable->dropColumn('description');
        $migrationTable->save();

        $this->expectException(StructureException::class);
        $this->expectExceptionMessage('Column "description" doesn\'t exist in table "test"');
        $structure->prepare($migrationTable);
    }

    public function testPrepareDropIndex()
    {
        $structure = $this->prepareComplexStructure();
        $this->assertTrue($structure->tableExists('test_1'));
        $testTable = $structure->getTable('test_1');
        $this->assertInstanceOf(Table::class, $testTable);
        $this->assertInstanceOf(Index::class, $testTable->getIndex('alias'));

        $migrationTable = new MigrationTable('test_1');
        $migrationTable->dropIndexByName('alias');
        $migrationTable->save();

        $preparedMigrationTable = $structure->prepare($migrationTable);
        $this->assertInstanceOf(MigrationTable::class, $preparedMigrationTable);
        $this->assertEquals($preparedMigrationTable, $migrationTable);
    }

    public function testPrepareDropNonExistingIndex()
    {
        $structure = $this->prepareComplexStructure();
        $this->assertTrue($structure->tableExists('test_1'));
        $testTable = $structure->getTable('test_1');
        $this->assertInstanceOf(Table::class, $testTable);
        $this->assertNull($testTable->getIndex('non_existing_index'));

        $migrationTable = new MigrationTable('test_1');
        $migrationTable->dropIndexByName('non_existing_index');
        $migrationTable->save();

        $this->expectException(StructureException::class);
        $this->expectExceptionMessage("Index 'non_existing_index' doesn't exist in table 'test_1'");
        $structure->prepare($migrationTable);
    }

    public function testPrepareDropForeignKey()
    {
        $structure = $this->prepareComplexStructure();
        $this->assertTrue($structure->tableExists('test_2'));
        $testTable = $structure->getTable('test_2');
        $this->assertInstanceOf(Table::class, $testTable);
        $this->assertInstanceOf(ForeignKey::class, $testTable->getForeignKey('fk_test_1_id'));

        $migrationTable = new MigrationTable('test_2');
        $migrationTable->dropForeignKey('fk_test_1_id');
        $migrationTable->save();

        $preparedMigrationTable = $structure->prepare($migrationTable);
        $this->assertInstanceOf(MigrationTable::class, $preparedMigrationTable);
        $this->assertEquals($preparedMigrationTable, $migrationTable);
    }

    public function testPrepareDropNonExistingForeignKey()
    {
        $structure = $this->prepareComplexStructure();
        $this->assertTrue($structure->tableExists('test_2'));
        $testTable = $structure->getTable('test_2');
        $this->assertInstanceOf(Table::class, $testTable);
        $this->assertNull($testTable->getForeignKey('non_existing_fk'));

        $migrationTable = new MigrationTable('test_2');
        $migrationTable->dropForeignKey('non_existing_fk');
        $migrationTable->save();

        $this->expectException(StructureException::class);
        $this->expectExceptionMessage("Foreign key 'non_existing_fk' doesn't exist in table 'test_2'");
        $structure->prepare($migrationTable);
    }

    public function testDropTableReferencedInAnotherTable()
    {
        $structure = $this->prepareComplexStructure();
        $migrationTable = new MigrationTable('test_1');
        $migrationTable->drop();

        $this->expectException(StructureException::class);
        $this->expectExceptionMessage('Table "test_1" is referenced in foreign key "fk_test_1_id" in table "test_2"');
        $structure->prepare($migrationTable);
    }

    public function testRenameTableReferencedInAnotherTableAndDropIt()
    {
        $structure = $this->prepareComplexStructure();
        $migrationRenameTable = new MigrationTable('test_1');
        $migrationRenameTable->rename('renamed_table_1');
        $structure->update($migrationRenameTable);

        $migrationTable = new MigrationTable('renamed_table_1');
        $migrationTable->drop();

        $this->expectException(StructureException::class);
        $this->expectExceptionMessage('Table "renamed_table_1" is referenced in foreign key "fk_test_1_id" in table "test_2"');
        $structure->prepare($migrationTable);
    }

    private function prepareSimpleStructure()
    {
        $structure = new Structure();
        $migrationTable = new MigrationTable('test');
        $migrationTable->addColumn('title', 'string');
        $migrationTable->addColumn('alias', 'string');
        $migrationTable->create();
        $this->assertInstanceOf(Structure::class, $structure->update($migrationTable));
        return $structure;
    }

    private function prepareComplexStructure()
    {
        $structure = new Structure();
        $migrationTable = new MigrationTable('test_1');
        $migrationTable->addColumn('title', 'string');
        $migrationTable->addColumn('alias', 'string');
        $migrationTable->addIndex('alias', Index::TYPE_UNIQUE, Index::METHOD_DEFAULT, 'alias');
        $migrationTable->create();
        $this->assertInstanceOf(Structure::class, $structure->update($migrationTable));

        $migrationTable2 = new MigrationTable('test_2');
        $migrationTable2->addColumn('title', 'string');
        $migrationTable2->addColumn('bodytext', 'text');
        $migrationTable2->addColumn('fk_test_1_id', 'integer');
        $migrationTable2->addForeignKey('fk_test_1_id', 'test_1');
        $migrationTable2->create();
        $this->assertInstanceOf(Structure::class, $structure->update($migrationTable2));

        return $structure;
    }
}
