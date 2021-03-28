<?php

namespace Phoenix\TestingMigrations;

use Phoenix\Database\Element\ColumnSettings;
use Phoenix\Database\Element\ForeignKey;
use Phoenix\Database\Element\IndexColumn;
use Phoenix\Migration\AbstractMigration;

class Init extends AbstractMigration
{
    protected function up(): void
    {
        $this->table('table_1')
            ->addColumn('title', 'string', ['charset' => 'utf16'])
            ->addColumn('alias', 'string', ['length' => 100])
            ->addColumn('is_active', 'boolean', ['default' => true])
            ->addColumn('bodytext', 'text', ['null' => true])
            ->addColumn('self_fk', 'integer', ['null' => true])
            ->addIndex(new IndexColumn('alias', ['length' => 10]), 'unique')
            ->addForeignKey('self_fk', 'table_1', 'id', ForeignKey::SET_NULL, ForeignKey::CASCADE)
            ->setAutoIncrement(10)
            ->create();

        $this->table('table_2')
            ->addColumn('title', 'string', ['charset' => 'utf16', 'collation' => 'utf16_slovak_ci'])
            ->addColumn('sorting', 'integer', ['default' => 100])
            ->addColumn('t1_fk', 'integer')
            ->addColumn('created_at', 'datetime')
            ->addIndex('sorting')
            ->addForeignKey('t1_fk', 'table_1', 'id')
            ->create();

        $this->table('table_3', false)
            ->addColumn('identifier', 'uuid')
            ->addColumn('t1_fk', 'integer')
            ->addColumn('t2_fk', 'integer', ['null' => true])
            ->addForeignKey('t1_fk', 'table_1', 'id', ForeignKey::RESTRICT, ForeignKey::RESTRICT)
            ->addForeignKey('t2_fk', 'table_2', 'id')
            ->create();

        $this->table('table_4', false)
            ->addColumn('title', 'string')
            ->create();

        $this->table('table_5', 'id')
            ->addColumn('id', 'smallinteger', ['autoincrement' => true])
            ->addColumn('title', 'string', ['length' => 100])
            ->create();

        $this->table('table_6')
            ->addColumn('title', 'string', ['length' => 100])
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
            ->addColumn('col_binary', 'binary', ['null' => true])
            ->addColumn('col_varbinary', 'varbinary', ['null' => true])
            ->addColumn('col_tinytext', 'tinytext', ['null' => true])
            ->addColumn('col_mediumtext', 'mediumtext', ['null' => true])
            ->addColumn('col_text', 'text', ['null' => true])
            ->addColumn('col_longtext', 'longtext', ['null' => true])
            ->addColumn('col_tinyblob', 'tinyblob', ['null' => true])
            ->addColumn('col_mediumblob', 'mediumblob', ['null' => true])
            ->addColumn('col_blob', 'blob', ['null' => true])
            ->addColumn('col_longblob', 'longblob', ['null' => true])
            ->addColumn('col_json', 'json')
            ->addColumn('col_numeric', 'numeric', ['length' => 10, 'decimals' => 3])
            ->addColumn('col_decimal', 'decimal', ['length' => 10, 'decimals' => 3])
            ->addColumn('col_float', 'float', ['length' => 10, 'decimals' => 3])
            ->addColumn('col_double', 'double', ['length' => 10, 'decimals' => 3])
            ->addColumn('col_boolean', 'boolean')
            ->addColumn('col_bit', 'bit', ['length' => 32, 'default' => "b'10101'"])
            ->addColumn('col_datetime', 'datetime')
            ->addColumn('col_date', 'date')
            ->addColumn('col_enum', 'enum', ['values' => ['xxx', 'yyy', 'zzz'], 'null' => true])
            ->addColumn('col_set', 'set', ['values' => ['xxx', 'yyy', 'zzz'], 'null' => true])
            ->addColumn('col_point', 'point', ['null' => true])
            ->addColumn('col_line', 'line', ['null' => true])
            ->addColumn('col_polygon', 'polygon', ['null' => true])
            ->addColumn('col_time', 'time', ['null' => true])
            ->addColumn('col_timestamp', 'timestamp', ['null' => true, 'default' => ColumnSettings::DEFAULT_VALUE_CURRENT_TIMESTAMP])
            ->addIndex([new IndexColumn('col_string', ['order' => 'DESC']), new IndexColumn('col_integer', ['order' => 'DESC'])])
            ->addForeignKey('col_smallinteger', 'table_5', 'id', ForeignKey::CASCADE, ForeignKey::CASCADE)
            ->create();
    }

    protected function down(): void
    {
        $this->table('all_types')->drop();
        $this->table('table_6')->drop();
        $this->table('table_5')->drop();
        $this->table('table_4')->drop();
        $this->table('table_3')->drop();
        $this->table('table_2')->drop();
        $this->table('table_1')->drop();
    }
}
