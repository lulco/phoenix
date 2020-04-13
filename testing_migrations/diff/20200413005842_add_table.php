<?php

use Phoenix\Migration\AbstractMigration;

class AddTable extends AbstractMigration
{
    protected function up(): void
    {
//        $this->table('table_3', 'id')
//            ->addColumn('id', 'uuid')
//            ->addColumn('name', 'string')
//            ->addColumn('description', 'text')
//            ->create();
    }

    protected function down(): void
    {
//        $this->table('table_3')->drop();
    }
}
