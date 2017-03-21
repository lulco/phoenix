<?php

namespace Phoenix\TestingMigrations;

use Phoenix\Database\Element\ForeignKey;
use Phoenix\Migration\AbstractMigration;

class Init extends AbstractMigration
{
    protected function up()
    {
        $this->table('table_1')
            ->addColumn('title', 'string', ['charset' => 'utf16'])
            ->addColumn('alias', 'string', ['length' => 100])
            ->addColumn('is_active', 'boolean', ['default' => false])
            ->addColumn('bodytext', 'text', ['null' => true])
            ->addColumn('self_fk', 'integer', ['null' => true])
            ->addIndex('alias', 'unique')
            ->addForeignKey('self_fk', 'table_1', 'id', ForeignKey::SET_NULL, ForeignKey::CASCADE)
            ->create();

        $this->table('table_2')
            ->addColumn('title', 'string', ['charset' => 'utf16', 'collation' => 'utf16_slovak_ci'])
            ->addColumn('sorting', 'integer', ['default' => 100])
            ->addColumn('t1_fk', 'integer')
            ->addColumn('created_at', 'datetime')
            ->addIndex('sorting')
            ->addForeignKey('t1_fk', 'table_1', 'id')
            ->create();

        $this->table('table_3', 'identifier')
            ->addColumn('identifier', 'uuid')
            ->addColumn('t1_fk', 'integer')
            ->addColumn('t2_fk', 'integer', ['null' => true])
            ->addForeignKey('t1_fk', 'table_1', 'id')
            ->addForeignKey('t2_fk', 'table_2', 'id')
            ->create();

        $this->table('all_types', 'identifier')
            ->addColumn('identifier', 'uuid')
            ->addColumn('col_tinyinteger', 'tinyinteger')
            ->addColumn('col_smallinteger', 'smallinteger')
            ->addColumn('col_mediuminteger', 'mediuminteger')
            ->addColumn('col_integer', 'integer')
            ->addColumn('col_bigint', 'biginteger')
            ->addColumn('col_string', 'string')
            ->addColumn('col_char', 'char')
            ->addColumn('col_text', 'text')
            ->addColumn('col_json', 'json')
            ->addColumn('col_numeric', 'numeric', ['length' => 10, 'decimals' => 3])
            ->addColumn('col_decimal', 'decimal', ['length' => 10, 'decimals' => 3])
            ->addColumn('col_float', 'float', ['length' => 10, 'decimals' => 3])
            ->addColumn('col_double', 'double', ['length' => 10, 'decimals' => 3])
            ->addColumn('col_boolean', 'boolean')
            ->addColumn('col_datetime', 'datetime')
            ->addColumn('col_date', 'date')
            ->addColumn('col_enum', 'enum', ['values' => ['xxx', 'yyy', 'zzz'], 'null' => true])
            ->addColumn('col_set', 'set', ['values' => ['xxx', 'yyy', 'zzz'], 'null' => true])
            ->addColumn('col_point', 'point', ['null' => true])
            ->addColumn('col_line', 'line', ['null' => true])
            ->addColumn('col_polygon', 'polygon', ['null' => true])
            ->create();
    }

    protected function down()
    {
        $this->table('all_types')->drop();
        $this->table('table_3')->drop();
        $this->table('table_2')->drop();
        $this->table('table_1')->drop();
    }
}
