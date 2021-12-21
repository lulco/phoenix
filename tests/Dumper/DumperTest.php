<?php

namespace Phoenix\Tests\Dumper;

use Dumper\Dumper;
use Phoenix\Database\Element\Column;
use Phoenix\Database\Element\ColumnSettings;
use Phoenix\Database\Element\ForeignKey;
use Phoenix\Database\Element\Index;
use Phoenix\Database\Element\IndexColumn;
use Phoenix\Database\Element\MigrationTable;
use PHPUnit\Framework\TestCase;

class DumperTest extends TestCase
{
    public function testIndent()
    {
        $migrationTable = (new MigrationTable('table_1', false))->addColumn('id', 'integer');
        $migrationTable->create();
        $tables = [$migrationTable];

        $dumper = new Dumper('    ');
        $output = "\$this->table('table_1', false)\n    ->addColumn('id', 'integer')\n    ->create();";
        $this->assertEquals($output, $dumper->dumpTables($tables, 'up'));

        $dumper = new Dumper("\t");
        $output = "\$this->table('table_1', false)\n\t->addColumn('id', 'integer')\n\t->create();";
        $this->assertEquals($output, $dumper->dumpTables($tables, 'up'));

        $dumper = new Dumper('custom_indent');
        $output = "\$this->table('table_1', false)\ncustom_indent->addColumn('id', 'integer')\ncustom_indent->create();";
        $this->assertEquals($output, $dumper->dumpTables($tables, 'up'));
    }

    public function testBaseIndent()
    {
        $migrationTable = (new MigrationTable('table_1', false))->addColumn('id', 'integer');
        $migrationTable->create();
        $tables = [$migrationTable];

        $dumper = new Dumper('    ', 2);
        $output = "        \$this->table('table_1', false)\n            ->addColumn('id', 'integer')\n            ->create();";
        $this->assertEquals($output, $dumper->dumpTables($tables, 'up'));

        $dumper = new Dumper("\t", 2);
        $output = "\t\t\$this->table('table_1', false)\n\t\t\t->addColumn('id', 'integer')\n\t\t\t->create();";
        $this->assertEquals($output, $dumper->dumpTables($tables, 'up'));

        $dumper = new Dumper('custom_indent', 2);
        $output = "custom_indentcustom_indent\$this->table('table_1', false)\ncustom_indentcustom_indentcustom_indent->addColumn('id', 'integer')\ncustom_indentcustom_indentcustom_indent->create();";
        $this->assertEquals($output, $dumper->dumpTables($tables, 'up'));
    }

    public function testDumpEmptyStructureUp()
    {
        $dumper = new Dumper('    ');
        $this->assertEquals('', $dumper->dumpTables([], 'up'));
        $this->assertEquals('', $dumper->dumpTables([], 'up'));
    }

    public function testDumpEmptyDataUp()
    {
        $dumper = new Dumper('    ');
        $this->assertEquals('', $dumper->dumpDataUp());
        $this->assertEquals('', $dumper->dumpDataUp([]));
    }

    public function testDumpEmptyForeignKeysUp()
    {
        $dumper = new Dumper('    ');
        $this->assertEquals('', $dumper->dumpForeignKeys([]));
        $this->assertEquals('', $dumper->dumpForeignKeys([]));
    }

    public function testDumpSimpleStructureUp()
    {
        $migrationTable = (new MigrationTable('table_1'))
            ->addColumn('title', 'string');
        $migrationTable->create();
        $tables = [$migrationTable];

        $dumper = new Dumper('    ');
        $output = "\$this->table('table_1', 'id')\n    ->addColumn('id', 'integer', ['autoincrement' => true])\n    ->addColumn('title', 'string')\n    ->create();";
        $this->assertEquals($output, $dumper->dumpTables($tables, 'up'));

        $dumper = new Dumper("\t");
        $output = "\$this->table('table_1', 'id')\n\t->addColumn('id', 'integer', ['autoincrement' => true])\n\t->addColumn('title', 'string')\n\t->create();";
        $this->assertEquals($output, $dumper->dumpTables($tables, 'up'));
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
            'table_2' => [],
        ];
        $dumper = new Dumper('    ');
        $output = "\$this->insert('table_1', [\n    [\n        'id' => '1',\n        'title' => 'Title 1',\n        'alias' => 'title-1',\n    ],\n    [\n        'id' => '2',\n        'title' => 'Title 2',\n        'alias' => 'title-2',\n    ],\n]);";
        $this->assertEquals($output, $dumper->dumpDataUp($data));

        $dumper = new Dumper("\t");
        $output = "\$this->insert('table_1', [\n\t[\n\t\t'id' => '1',\n\t\t'title' => 'Title 1',\n\t\t'alias' => 'title-1',\n\t],\n\t[\n\t\t'id' => '2',\n\t\t'title' => 'Title 2',\n\t\t'alias' => 'title-2',\n\t],\n]);";
        $this->assertEquals($output, $dumper->dumpDataUp($data));
    }

    public function testDumpComplexStructureUp()
    {
        $tables = $this->createComplexStructure();

        $dumper = new Dumper('    ');
        $output = "\$this->table('table_1', 'id')\n    ->setCharset('utf8')\n    ->setCollation('utf8_general_ci')\n    ->addColumn('id', 'integer', ['autoincrement' => true])\n    ->addColumn('title', 'string', ['charset' => 'utf16', 'collation' => 'utf16_general_ci'])\n    ->addColumn('alias', 'string')\n    ->addColumn('bodytext', 'text')\n    ->addColumn('price', 'decimal')\n    ->addColumn('sorting', 'biginteger')\n    ->addIndex(new \Phoenix\Database\Element\IndexColumn('alias', ['length' => 10]), 'unique', '', 'idx_table_1_alias_l10')\n    ->create();\n\n";
        $output .= "\$this->table('table_2', 'id')\n    ->addColumn('id', 'integer', ['autoincrement' => true])\n    ->addColumn('title', 'string', ['length' => 100])\n    ->addColumn('is_active', 'boolean', ['default' => true])\n    ->addColumn('fk_table_1_id', 'integer')\n    ->create();\n\n";

        $output .= "\$this->table('table_3', 'id')\n    ->addColumn('id', 'integer', ['autoincrement' => true])\n    ->addColumn('title', 'string')\n    ->addColumn('status', 'enum', ['values' => ['new', 'processed', 'done'], 'default' => 'new'])\n    ->addColumn('fk_table_1_id', 'integer', ['null' => true])\n    ->create();\n\n";
        $output .= "\$this->table('table_4', 'identifier')\n    ->addColumn('identifier', 'uuid')\n    ->addColumn('col_integer', 'integer')\n    ->addColumn('col_string', 'string')\n    ->addColumn('col_char', 'char')\n    ->addColumn('col_timestamp', 'timestamp', ['null' => true, 'default' => 'CURRENT_TIMESTAMP'])\n    ->addIndex([new \Phoenix\Database\Element\IndexColumn('col_string', ['order' => 'DESC']), new \Phoenix\Database\Element\IndexColumn('col_integer', ['order' => 'DESC'])], '', '', 'idx_table_4_col_string_odesc_col_integer_odesc')\n    ->create();";
        $this->assertEquals($output, $dumper->dumpTables($tables, 'up'));

        $dumper = new Dumper("\t");
        $output = "\$this->table('table_1', 'id')\n\t->setCharset('utf8')\n\t->setCollation('utf8_general_ci')\n\t->addColumn('id', 'integer', ['autoincrement' => true])\n\t->addColumn('title', 'string', ['charset' => 'utf16', 'collation' => 'utf16_general_ci'])\n\t->addColumn('alias', 'string')\n\t->addColumn('bodytext', 'text')\n\t->addColumn('price', 'decimal')\n\t->addColumn('sorting', 'biginteger')\n\t->addIndex(new \Phoenix\Database\Element\IndexColumn('alias', ['length' => 10]), 'unique', '', 'idx_table_1_alias_l10')\n\t->create();\n\n";
        $output .= "\$this->table('table_2', 'id')\n\t->addColumn('id', 'integer', ['autoincrement' => true])\n\t->addColumn('title', 'string', ['length' => 100])\n\t->addColumn('is_active', 'boolean', ['default' => true])\n\t->addColumn('fk_table_1_id', 'integer')\n\t->create();\n\n";

        $output .= "\$this->table('table_3', 'id')\n\t->addColumn('id', 'integer', ['autoincrement' => true])\n\t->addColumn('title', 'string')\n\t->addColumn('status', 'enum', ['values' => ['new', 'processed', 'done'], 'default' => 'new'])\n\t->addColumn('fk_table_1_id', 'integer', ['null' => true])\n\t->create();\n\n";
        $output .= "\$this->table('table_4', 'identifier')\n\t->addColumn('identifier', 'uuid')\n\t->addColumn('col_integer', 'integer')\n\t->addColumn('col_string', 'string')\n\t->addColumn('col_char', 'char')\n\t->addColumn('col_timestamp', 'timestamp', ['null' => true, 'default' => 'CURRENT_TIMESTAMP'])\n\t->addIndex([new \Phoenix\Database\Element\IndexColumn('col_string', ['order' => 'DESC']), new \Phoenix\Database\Element\IndexColumn('col_integer', ['order' => 'DESC'])], '', '', 'idx_table_4_col_string_odesc_col_integer_odesc')\n\t->create();";
        $this->assertEquals($output, $dumper->dumpTables($tables, 'up'));
    }

    public function testDumpComplexDataUp()
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
            'table_2' => [
                [
                    'id' => 1,
                    'title' => 'Title 1',
                    'alias' => 'title-1',
                    'fk_table_1_id' => 1,
                ],
                [
                    'id' => 2,
                    'title' => 'Title 2',
                    'alias' => 'title-2',
                    'fk_table_1_id' => 1,
                ],
            ],
        ];
        $dumper = new Dumper('    ');
        $output = "\$this->insert('table_1', [\n    [\n        'id' => '1',\n        'title' => 'Title 1',\n        'alias' => 'title-1',\n    ],\n    [\n        'id' => '2',\n        'title' => 'Title 2',\n        'alias' => 'title-2',\n    ],\n]);\n\n";
        $output .= "\$this->insert('table_2', [\n    [\n        'id' => '1',\n        'title' => 'Title 1',\n        'alias' => 'title-1',\n        'fk_table_1_id' => '1',\n    ],\n    [\n        'id' => '2',\n        'title' => 'Title 2',\n        'alias' => 'title-2',\n        'fk_table_1_id' => '1',\n    ],\n]);";
        $this->assertEquals($output, $dumper->dumpDataUp($data));

        $dumper = new Dumper("\t");
        $output = "\$this->insert('table_1', [\n\t[\n\t\t'id' => '1',\n\t\t'title' => 'Title 1',\n\t\t'alias' => 'title-1',\n\t],\n\t[\n\t\t'id' => '2',\n\t\t'title' => 'Title 2',\n\t\t'alias' => 'title-2',\n\t],\n]);\n\n";
        $output .= "\$this->insert('table_2', [\n\t[\n\t\t'id' => '1',\n\t\t'title' => 'Title 1',\n\t\t'alias' => 'title-1',\n\t\t'fk_table_1_id' => '1',\n\t],\n\t[\n\t\t'id' => '2',\n\t\t'title' => 'Title 2',\n\t\t'alias' => 'title-2',\n\t\t'fk_table_1_id' => '1',\n\t],\n]);";
        $this->assertEquals($output, $dumper->dumpDataUp($data));
    }

    public function testdumpForeignKeys()
    {
        $tables = $this->createComplexStructure();

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
        $dumper = new Dumper('    ');
        $this->assertEquals('', $dumper->dumpTables([], 'down'));
        $this->assertEquals('', $dumper->dumpTables([], 'down'));
    }

    public function testDumpEmptyForeignKeysDown()
    {
        $dumper = new Dumper('    ');
        $this->assertEquals('', $dumper->dumpForeignKeys([]));
        $this->assertEquals('', $dumper->dumpForeignKeys([]));
    }

    public function testDumpSimpleStructureDrop()
    {
        $migrationTable = (new MigrationTable('table_1'));
        $migrationTable->drop();
        $tables = [$migrationTable];

        $dumper = new Dumper('    ');
        $output = "\$this->table('table_1')\n    ->drop();";
        $this->assertEquals($output, $dumper->dumpTables($tables, 'down'));

        $dumper = new Dumper("\t");
        $output = "\$this->table('table_1')\n\t->drop();";
        $this->assertEquals($output, $dumper->dumpTables($tables, 'down'));
    }

    public function testComplexChanges()
    {
        $tables = [
            (new MigrationTable('table_1'))
                ->dropColumn('created_at')
                ->changeColumn('sorting', 'sorting', 'integer', ['default' => 100])
                ->addPrimaryColumns([new Column('id', 'integer', ['autoincrement' => true])])
                ->addColumn('description', 'text')
                ->addIndex('alias', Index::TYPE_UNIQUE),
            (new MigrationTable('table_2'))
                ->dropPrimaryKey()
                ->changeColumn('description', 'description', 'text')
                ->addColumn('table_1_fk', 'integer', ['null' => true])
                ->dropIndexByName('idx_table_2_alias')
                ->addForeignKey('table_1_fk', 'table_1', 'id', ForeignKey::SET_NULL, ForeignKey::CASCADE)
                ->dropForeignKey('table_3_fk')
        ];

        $dumper = new Dumper('    ');
        $output = "\$this->table('table_1')\n    ->dropColumn('created_at')\n    ->changeColumn('sorting', 'sorting', 'integer', ['default' => 100])\n    ->addPrimaryColumns([new \\Phoenix\\Database\\Element\\Column('id', 'integer', ['autoincrement' => true])])\n    ->addColumn('description', 'text')\n    ->addIndex('alias', 'unique', '', 'idx_table_1_alias')\n    ->save();\n\n";
        $output .= "\$this->table('table_2')\n    ->dropPrimaryKey()\n    ->changeColumn('description', 'description', 'text')\n    ->addColumn('table_1_fk', 'integer', ['null' => true])\n    ->dropIndexByName('idx_table_2_alias')\n    ->save();";
        $this->assertEquals($output, $dumper->dumpTables($tables, 'up'));

        $output = "\$this->table('table_2')\n    ->dropForeignKey('table_3_fk')\n    ->addForeignKey('table_1_fk', 'table_1', 'id', 'set null', 'cascade')\n    ->save();";
        $this->assertEquals($output, $dumper->dumpForeignKeys($tables));
    }

    private function createComplexStructure(): array
    {
        $migrationTables = [];

        $table1 = new MigrationTable('table_1');
        $table1->setCharset('utf8');
        $table1->setCollation('utf8_general_ci');
        $table1->addColumn('title', 'string', ['length' => 255, 'charset' => 'utf16', 'collation' => 'utf16_general_ci']);
        $table1->addColumn('alias', 'string', ['charset' => 'utf8', 'collation' => 'utf8_general_ci']);
        $table1->addColumn('bodytext', 'text');
        $table1->addColumn('price', 'decimal');
        $table1->addColumn('sorting', 'biginteger');
        $table1->addIndex(new IndexColumn('alias', ['length' => 10]), 'unique');
        $table1->create();
        $migrationTables[] = $table1;

        $table2 = new MigrationTable('table_2');
        $table2->addColumn('title', 'string', ['length' => 100]);
        $table2->addColumn('is_active', 'boolean', ['default' => true]);
        $table2->addColumn('fk_table_1_id', 'integer', ['null' => false]);
        $table2->addForeignKey(['fk_table_1_id'], 'table_1');
        $table2->create();
        $migrationTables[] = $table2;

        $table3 = new MigrationTable('table_3');
        $table3->addColumn('title', 'string');
        $table3->addColumn('status', 'enum', ['values' => ['new', 'processed', 'done'], 'default' => 'new']);
        $table3->addColumn('fk_table_1_id', 'integer', ['null' => true, 'default' => null]);
        $table3->addForeignKey(['fk_table_1_id'], 'table_1', 'id', ForeignKey::SET_NULL, ForeignKey::CASCADE);
        $table3->create();
        $migrationTables[] = $table3;

        $table4 = new MigrationTable('table_4', 'identifier');
        $table4->addColumn('identifier', 'uuid');
        $table4->addColumn('col_integer', 'integer');
        $table4->addColumn('col_string', 'string');
        $table4->addColumn('col_char', 'char');
        $table4->addColumn('col_timestamp', 'timestamp', ['null' => true, 'default' => ColumnSettings::DEFAULT_VALUE_CURRENT_TIMESTAMP]);
        $table4->addIndex([new IndexColumn('col_string', ['order' => 'DESC']), new IndexColumn('col_integer', ['order' => 'DESC'])]);
        $table4->create();
        $migrationTables[] = $table4;

        return $migrationTables;
    }
}
