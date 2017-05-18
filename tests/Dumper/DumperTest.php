<?php

namespace Phoenix\Tests\Dumper;

use Dumper\Dumper;
use Phoenix\Database\Element\Column;
use Phoenix\Database\Element\ForeignKey;
use Phoenix\Database\Element\MigrationTable;
use Phoenix\Database\Element\Structure;
use Phoenix\Database\Element\Table;
use PHPUnit_Framework_TestCase;

class DumperTest extends PHPUnit_Framework_TestCase
{
    public function testIndent()
    {
        $tables = [(new Table('table_1'))->addColumn(new Column('id', 'integer'))];

        $dumper = new Dumper('    ');
        $output = "\$this->table('table_1')\n    ->addColumn('id', 'integer')\n    ->create();";
        $this->assertEquals($output, $dumper->dumpTables($tables));

        $dumper = new Dumper("\t");
        $output = "\$this->table('table_1')\n\t->addColumn('id', 'integer')\n\t->create();";
        $this->assertEquals($output, $dumper->dumpTables($tables));

        $dumper = new Dumper('custom_indent');
        $output = "\$this->table('table_1')\ncustom_indent->addColumn('id', 'integer')\ncustom_indent->create();";
        $this->assertEquals($output, $dumper->dumpTables($tables));
    }

    public function testBaseIndent()
    {
        $tables = [(new Table('table_1'))->addColumn(new Column('id', 'integer'))];

        $dumper = new Dumper('    ', 2);
        $output = "        \$this->table('table_1')\n            ->addColumn('id', 'integer')\n            ->create();";
        $this->assertEquals($output, $dumper->dumpTables($tables));

        $dumper = new Dumper("\t", 2);
        $output = "\t\t\$this->table('table_1')\n\t\t\t->addColumn('id', 'integer')\n\t\t\t->create();";
        $this->assertEquals($output, $dumper->dumpTables($tables));

        $dumper = new Dumper('custom_indent', 2);
        $output = "custom_indentcustom_indent\$this->table('table_1')\ncustom_indentcustom_indentcustom_indent->addColumn('id', 'integer')\ncustom_indentcustom_indentcustom_indent->create();";
        $this->assertEquals($output, $dumper->dumpTables($tables));
    }

    public function testDumpEmptyStructureUp()
    {
        $dumper = new Dumper('    ');
        $this->assertEquals('', $dumper->dumpTables());
        $this->assertEquals('', $dumper->dumpTables([]));
    }

    public function testDumpEmptyDataUp()
    {
        $dumper = new Dumper('    ');
        $this->assertEquals('', $dumper->dumpData());
        $this->assertEquals('', $dumper->dumpData([]));
    }

    public function testDumpEmptyForeignKeysUp()
    {
        $dumper = new Dumper('    ');
        $this->assertEquals('', $dumper->dumpForeignKeys());
        $this->assertEquals('', $dumper->dumpForeignKeys([]));
    }

    public function testDumpSimpleStructureUp()
    {
        $tables = [(new Table('table_1'))
            ->addColumn(new Column('id', 'integer', ['autoincrement' => true]))
            ->addColumn(new Column('title', 'string')),
        ];

        $dumper = new Dumper('    ');
        $output = "\$this->table('table_1')\n    ->addColumn('id', 'integer', ['autoincrement' => true])\n    ->addColumn('title', 'string')\n    ->create();";
        $this->assertEquals($output, $dumper->dumpTables($tables));

        $dumper = new Dumper("\t");
        $output = "\$this->table('table_1')\n\t->addColumn('id', 'integer', ['autoincrement' => true])\n\t->addColumn('title', 'string')\n\t->create();";
        $this->assertEquals($output, $dumper->dumpTables($tables));
    }

    public function testDumpSimpleDataUp()
    {
        $data = [
            'table_1' => [
                [
                    'id' => 1,
                    'title' => 'Title 1',
                    'alias' => 'title-1',
                ],
                [
                    'id' => 2,
                    'title' => 'Title 2',
                    'alias' => 'title-2',
                ],
            ],
        ];
        $dumper = new Dumper('    ');
        $output = "\$this->insert('table_1', [\n    [\n        'id' => '1',\n        'title' => 'Title 1',\n        'alias' => 'title-1',\n    ],\n    [\n        'id' => '2',\n        'title' => 'Title 2',\n        'alias' => 'title-2',\n    ],\n]);";
        $this->assertEquals($output, $dumper->dumpData($data));

        $dumper = new Dumper("\t");
        $output = "\$this->insert('table_1', [\n\t[\n\t\t'id' => '1',\n\t\t'title' => 'Title 1',\n\t\t'alias' => 'title-1',\n\t],\n\t[\n\t\t'id' => '2',\n\t\t'title' => 'Title 2',\n\t\t'alias' => 'title-2',\n\t],\n]);";
        $this->assertEquals($output, $dumper->dumpData($data));
    }

    public function testDumpComplexStructureUp()
    {
        $tables = $this->createComplexStructure()->getTables();

        $dumper = new Dumper('    ');
        $output = "\$this->table('table_1', 'id')\n    ->setCharset('utf8')\n    ->setCollation('utf8_general_ci')\n    ->addColumn('id', 'integer', ['autoincrement' => true])\n    ->addColumn('title', 'string', ['charset' => 'utf16', 'collation' => 'utf16_general_ci'])\n    ->addColumn('alias', 'string')\n    ->addColumn('bodytext', 'text')\n    ->addIndex('alias', 'unique', '', 'idx_table_1_alias')\n    ->create();\n\n";
        $output .= "\$this->table('table_2', 'id')\n    ->addColumn('id', 'integer', ['autoincrement' => true])\n    ->addColumn('title', 'string', ['length' => 100])\n    ->addColumn('is_active', 'boolean', ['default' => true])\n    ->addColumn('fk_table_1_id', 'integer')\n    ->create();\n\n";
        $output .= "\$this->table('table_3', 'id')\n    ->addColumn('id', 'integer', ['autoincrement' => true])\n    ->addColumn('title', 'string')\n    ->addColumn('status', 'enum', ['values' => ['new', 'processed', 'done'], 'default' => 'new'])\n    ->addColumn('fk_table_1_id', 'integer', ['null' => true])\n    ->create();";
        $this->assertEquals($output, $dumper->dumpTables($tables));

        $dumper = new Dumper("\t");
        $output = "\$this->table('table_1', 'id')\n\t->setCharset('utf8')\n\t->setCollation('utf8_general_ci')\n\t->addColumn('id', 'integer', ['autoincrement' => true])\n\t->addColumn('title', 'string', ['charset' => 'utf16', 'collation' => 'utf16_general_ci'])\n\t->addColumn('alias', 'string')\n\t->addColumn('bodytext', 'text')\n\t->addIndex('alias', 'unique', '', 'idx_table_1_alias')\n\t->create();\n\n";
        $output .= "\$this->table('table_2', 'id')\n\t->addColumn('id', 'integer', ['autoincrement' => true])\n\t->addColumn('title', 'string', ['length' => 100])\n\t->addColumn('is_active', 'boolean', ['default' => true])\n\t->addColumn('fk_table_1_id', 'integer')\n\t->create();\n\n";
        $output .= "\$this->table('table_3', 'id')\n\t->addColumn('id', 'integer', ['autoincrement' => true])\n\t->addColumn('title', 'string')\n\t->addColumn('status', 'enum', ['values' => ['new', 'processed', 'done'], 'default' => 'new'])\n\t->addColumn('fk_table_1_id', 'integer', ['null' => true])\n\t->create();";
        $this->assertEquals($output, $dumper->dumpTables($tables));
    }

    public function testDumpComplexDataUp()
    {

    }

    public function testDumpForeignKeysUp()
    {
        $tables = $this->createComplexStructure()->getTables();

        $dumper = new Dumper('    ');
        $output = "\$this->table('table_2')\n    ->addForeignKey('fk_table_1_id', 'table_1')\n    ->save();\n\n";
        $output .= "\$this->table('table_3')\n    ->addForeignKey('fk_table_1_id', 'table_1', 'id', 'set null', 'cascade')\n    ->save();";
        $this->assertEquals($output, $dumper->dumpForeignKeys($tables));

        $dumper = new Dumper("\t");
        $output = "\$this->table('table_2')\n\t->addForeignKey('fk_table_1_id', 'table_1')\n\t->save();\n\n";
        $output .= "\$this->table('table_3')\n\t->addForeignKey('fk_table_1_id', 'table_1', 'id', 'set null', 'cascade')\n\t->save();";
        $this->assertEquals($output, $dumper->dumpForeignKeys($tables));
    }

    public function testDumpEmptyStructureDown()
    {

    }

    public function testDumpEmptyDataDown()
    {

    }

    public function testDumpEmptyForeignKeysDown()
    {

    }

    public function testDumpSimpleStructureDown()
    {

    }

    public function testDumpSimpleDataDown()
    {

    }

    public function testDumpComplexStructureDown()
    {

    }

    public function testDumpComplexDataDown()
    {

    }

    public function testDumpForeignKeysDown()
    {

    }

    private function createComplexStructure()
    {
        $structure = new Structure();

        $table1 = new MigrationTable('table_1');
        $table1->setCharset('utf8');
        $table1->setCollation('utf8_general_ci');
        $table1->addColumn('title', 'string', ['length' => 255, 'charset' => 'utf16', 'collation' => 'utf16_general_ci']);
        $table1->addColumn('alias', 'string', ['charset' => 'utf8', 'collation' => 'utf8_general_ci']);
        $table1->addColumn('bodytext', 'text');
        $table1->addIndex(['alias'], 'unique');
        $table1->create();
        $structure->update($table1);

        $table2 = new MigrationTable('table_2');
        $table2->addColumn('title', 'string', ['length' => 100]);
        $table2->addColumn('is_active', 'boolean', ['default' => true]);
        $table2->addColumn('fk_table_1_id', 'integer', ['null' => false]);
        $table2->addForeignKey(['fk_table_1_id'], 'table_1');
        $table2->create();
        $structure->update($table2);

        $table3 = new MigrationTable('table_3');
        $table3->addColumn('title', 'string');
        $table3->addColumn('status', 'enum', ['values' => ['new', 'processed', 'done'], 'default' => 'new']);
        $table3->addColumn('fk_table_1_id', 'integer', ['null' => true, 'default' => null]);
        $table3->addForeignKey(['fk_table_1_id'], 'table_1', 'id', ForeignKey::SET_NULL, ForeignKey::CASCADE);
        $table3->create();
        $structure->update($table3);

        return $structure;
    }
}
