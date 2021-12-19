<?php

declare(strict_types=1);

namespace Phoenix\Tests\Comparator;

use Phoenix\Comparator\StructureComparator;
use Phoenix\Database\Element\Column;
use Phoenix\Database\Element\ForeignKey;
use Phoenix\Database\Element\Index;
use Phoenix\Database\Element\IndexColumn;
use Phoenix\Database\Element\MigrationTable;
use Phoenix\Database\Element\Structure;
use Phoenix\Database\Element\Table;
use PHPUnit\Framework\TestCase;

final class StructureComparatorTest extends TestCase
{
    public function testAddColumn(): void
    {
        $sourceStructure = new Structure();
        $sourceStructure->add((new Table('comparator_test'))->addColumn(new Column('title', 'string')));

        $targetStructure = new Structure();
        $targetTable = (new Table('comparator_test'))
            ->addColumn(new Column('title', 'string'))
            ->addColumn(new Column('description', 'text'));
        $targetStructure->add($targetTable);

        $structureComparator = new StructureComparator();
        $expectedDiff = [(new MigrationTable('comparator_test'))->addColumn('description', 'text')];
        $this->assertEquals($expectedDiff, $structureComparator->diff($sourceStructure, $targetStructure));
    }

    public function testDropColumn(): void
    {
        $sourceStructure = new Structure();
        $sourceTable = (new Table('comparator_test'))
            ->addColumn(new Column('title', 'string'))
            ->addColumn(new Column('description', 'text'));
        $sourceStructure->add($sourceTable);

        $targetStructure = new Structure();
        $targetStructure->add((new Table('comparator_test'))->addColumn(new Column('title', 'string')));

        $structureComparator = new StructureComparator();
        $expectedDiff = [(new MigrationTable('comparator_test'))->dropColumn('description')];
        $this->assertEquals($expectedDiff, $structureComparator->diff($sourceStructure, $targetStructure));
    }

    public function testAddTable(): void
    {
        $sourceStructure = new Structure();
        $sourceTable = (new Table('table1'))
            ->addColumn(new Column('title', 'string'))
            ->addColumn(new Column('description', 'text'));
        $sourceStructure->add($sourceTable);

        $targetStructure = new Structure();
        $targetStructure->add($sourceTable);
        $targetStructure->add((new Table('table2'))->addColumn(new Column('title', 'string')));

        $structureComparator = new StructureComparator();
        $addTableMigration = (new MigrationTable('table2', []))->addColumn('title', 'string');
        $addTableMigration->create();
        $expectedDiff = [$addTableMigration];
        $this->assertEquals($expectedDiff, $structureComparator->diff($sourceStructure, $targetStructure));
    }

    public function testDropTable(): void
    {
        $sourceStructure = new Structure();
        $sourceTable = (new Table('table1'))
            ->addColumn(new Column('title', 'string'))
            ->addColumn(new Column('description', 'text'));
        $sourceStructure->add($sourceTable);
        $sourceStructure->add((new Table('table2'))->addColumn(new Column('title', 'string')));

        $targetStructure = new Structure();
        $targetStructure->add((new Table('table2'))->addColumn(new Column('title', 'string')));

        $structureComparator = new StructureComparator();
        $dropTableMigration = new MigrationTable('table1');
        $dropTableMigration->drop();
        $expectedDiff = [$dropTableMigration];
        $this->assertEquals($expectedDiff, $structureComparator->diff($sourceStructure, $targetStructure));
    }

    public function testChangeColumn(): void
    {
        $sourceStructure = new Structure();
        $sourceStructure->add((new Table('comparator_test'))->addColumn(new Column('title', 'string')));

        $targetStructure = new Structure();
        $targetTable = (new Table('comparator_test'))
            ->addColumn(new Column('title', 'string', ['null' => true, 'length' => 1000, 'comment' => 'We need longer text']));
        $targetStructure->add($targetTable);

        $structureComparator = new StructureComparator();
        $expectedDiff = [(new MigrationTable('comparator_test'))->changeColumn('title', 'title', 'string', ['null' => true, 'length' => 1000, 'comment' => 'We need longer text'])];
        $this->assertEquals($expectedDiff, $structureComparator->diff($sourceStructure, $targetStructure));
    }

    public function testAddIndex(): void
    {
        $sourceStructure = new Structure();
        $sourceStructure->add((new Table('comparator_test'))
            ->addColumn(new Column('title', 'string'))
            ->addColumn(new Column('sorting', 'integer')));

        $targetStructure = new Structure();
        $targetStructure->add((new Table('comparator_test'))
            ->addColumn(new Column('title', 'string'))
            ->addColumn(new Column('sorting', 'integer'))
            ->addIndex(new Index([new IndexColumn('sorting')], 'idx_comparator_test_sorting'))
        );

        $structureComparator = new StructureComparator();
        $expectedDiff = [(new MigrationTable('comparator_test'))->addIndex('sorting', Index::TYPE_NORMAL, Index::METHOD_DEFAULT)];
        $this->assertEquals($expectedDiff, $structureComparator->diff($sourceStructure, $targetStructure));
    }

    public function testDropIndex(): void
    {
        $sourceStructure = new Structure();
        $sourceStructure->add((new Table('comparator_test'))
            ->addColumn(new Column('title', 'string'))
            ->addColumn(new Column('sorting', 'integer'))
            ->addIndex(new Index([new IndexColumn('sorting')], 'idx_comparator_test_sorting'))
        );
        $targetStructure = new Structure();
        $targetStructure->add((new Table('comparator_test'))
            ->addColumn(new Column('title', 'string'))
            ->addColumn(new Column('sorting', 'integer'))
        );

        $structureComparator = new StructureComparator();
        $expectedDiff = [(new MigrationTable('comparator_test'))->dropIndexByName('idx_comparator_test_sorting')];
        $this->assertEquals($expectedDiff, $structureComparator->diff($sourceStructure, $targetStructure));
    }

    public function testAddForeignKey(): void
    {
        $sourceStructure = new Structure();
        $sourceStructure->add((new Table('table_1'))
            ->setPrimary(['id'])
            ->addColumn(new Column('id', 'integer', ['autoincrement' => true]))
            ->addColumn(new Column('title', 'string'))
            ->addColumn(new Column('sorting', 'integer')));

        $sourceStructure->add((new Table('table_2'))
            ->setPrimary(['id'])
            ->addColumn(new Column('id', 'integer', ['autoincrement' => true]))
            ->addColumn(new Column('title', 'string'))
            ->addColumn(new Column('fk_table_1', 'integer')));

        $targetStructure = new Structure();
        $targetStructure->add((new Table('table_1'))
            ->setPrimary(['id'])
            ->addColumn(new Column('id', 'integer', ['autoincrement' => true]))
            ->addColumn(new Column('title', 'string'))
            ->addColumn(new Column('sorting', 'integer')));
        $targetStructure->add((new Table('table_2'))
            ->setPrimary(['id'])
            ->addColumn(new Column('id', 'integer', ['autoincrement' => true]))
            ->addColumn(new Column('title', 'string'))
            ->addColumn(new Column('fk_table_1', 'integer'))
            ->addForeignKey(new ForeignKey(['fk_table_1'], 'table_1', ['id'], ForeignKey::CASCADE, ForeignKey::CASCADE))
        );

        $structureComparator = new StructureComparator();
        $expectedDiff = [(new MigrationTable('table_2'))->addForeignKey('fk_table_1', 'table_1', ['id'], ForeignKey::CASCADE, ForeignKey::CASCADE)];
        $this->assertEquals($expectedDiff, $structureComparator->diff($sourceStructure, $targetStructure));
    }

    public function testDropForeignKey(): void
    {
        $sourceStructure = new Structure();
        $sourceStructure->add((new Table('table_1'))
            ->setPrimary(['id'])
            ->addColumn(new Column('id', 'integer', ['autoincrement' => true]))
            ->addColumn(new Column('title', 'string'))
            ->addColumn(new Column('sorting', 'integer')));

        $sourceStructure->add((new Table('table_2'))
            ->setPrimary(['id'])
            ->addColumn(new Column('id', 'integer', ['autoincrement' => true]))
            ->addColumn(new Column('title', 'string'))
            ->addColumn(new Column('fk_table_1', 'integer'))
            ->addForeignKey(new ForeignKey(['fk_table_1'], 'table_1', ['id'], ForeignKey::CASCADE, ForeignKey::CASCADE))
        );

        $targetStructure = new Structure();
        $targetStructure->add((new Table('table_1'))
            ->setPrimary(['id'])
            ->addColumn(new Column('id', 'integer', ['autoincrement' => true]))
            ->addColumn(new Column('title', 'string'))
            ->addColumn(new Column('sorting', 'integer')));
        $targetStructure->add((new Table('table_2'))
            ->setPrimary(['id'])
            ->addColumn(new Column('id', 'integer', ['autoincrement' => true]))
            ->addColumn(new Column('title', 'string'))
            ->addColumn(new Column('fk_table_1', 'integer'))
        );

        $structureComparator = new StructureComparator();
        $expectedDiff = [(new MigrationTable('table_2'))->dropForeignKey('fk_table_1')];
        $this->assertEquals($expectedDiff, $structureComparator->diff($sourceStructure, $targetStructure));
    }

    public function testMoreComplexChanges(): void
    {
        $sourceStructure = new Structure();
        $sourceStructure->add(
            (new Table('table_1'))
                ->addColumn(new Column('title', 'string'))
                ->addColumn(new Column('alias', 'string'))
                ->addColumn(new Column('created_at', 'datetime'))
                ->addColumn(new Column('sorting', 'integer', ['signed' => false, 'default' => 0]))
        );

        $sourceStructure->add(
            (new Table('table_2'))
                ->setPrimary(['id'])
                ->addColumn(new Column('id', 'integer', ['autoincrement' => true]))
                ->addColumn(new Column('title', 'string'))
                ->addColumn(new Column('description', 'string'))
        );

        $targetStructure = new Structure();
        $targetStructure->add((new Table('table_1'))
            ->setPrimary(['id'])
            ->addColumn(new Column('id', 'integer', ['autoincrement' => true]))
            ->addColumn(new Column('title', 'string'))
            ->addColumn(new Column('alias', 'string'))
            ->addColumn(new Column('sorting', 'integer', ['default' => 100]))
            ->addColumn(new Column('description', 'text'))
            ->addIndex(new Index([new IndexColumn('alias')], 'idx_table_1_alias', Index::TYPE_UNIQUE, Index::METHOD_DEFAULT))
        );
        $targetStructure->add((new Table('table_2'))
            ->addColumn(new Column('title', 'string'))
            ->addColumn(new Column('description', 'text'))
            ->addColumn(new Column('table_1_fk', 'integer', ['null' => true]))
            ->addForeignKey(new ForeignKey(['table_1_fk'], 'table_1', ['id'], ForeignKey::SET_NULL, ForeignKey::CASCADE))
        );

        $structureComparator = new StructureComparator();
        $expectedDiff = [
            (new MigrationTable('table_1'))
                ->addPrimaryColumns([new Column('id', 'integer', ['autoincrement' => true])])
                ->dropColumn('created_at')
                ->changeColumn('sorting', 'sorting', 'integer', ['default' => 100])
                ->addColumn('description', 'text')
                ->addIndex('alias', Index::TYPE_UNIQUE),
            (new MigrationTable('table_2'))
                ->dropPrimaryKey()
                ->changeColumn('description', 'description', 'text')
                ->addColumn('table_1_fk', 'integer', ['null' => true])
                ->addForeignKey('table_1_fk', 'table_1', 'id', ForeignKey::SET_NULL, ForeignKey::CASCADE)
        ];
        $this->assertEquals($expectedDiff, $structureComparator->diff($sourceStructure, $targetStructure));
    }
}
