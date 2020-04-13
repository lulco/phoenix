<?php

use Phoenix\Database\Element\ForeignKey;
use Phoenix\Migration\AbstractMigration;

class Init extends AbstractMigration
{
    protected function up(): void
    {
        $this->table('table_1')
            ->addColumn('title', 'string', ['charset' => 'utf8', 'collation' => 'utf8_general_ci'])
            ->addColumn('alias', 'string')
            ->addColumn('sorting', 'integer')
            ->addIndex('alias', 'UNIQUE')
            ->create();

//        $this->table('table_2')
//            ->addColumn('title', 'string')
//            ->addColumn('fk_table_1', 'integer')
//            ->addForeignKey('fk_table_1', 'table_1', 'id', ForeignKey::CASCADE, ForeignKey::CASCADE)
//            ->create();
    }

    protected function down(): void
    {
//        $this->table('table_2')->drop();
        $this->table('table_1')->drop();
    }
}
