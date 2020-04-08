<?php

namespace Phoenix\TestingMigrations;

use Phoenix\Database\Element\IndexColumn;
use Phoenix\Migration\AbstractMigration;

class RemoveSomeIndexes extends AbstractMigration
{
    public function up(): void
    {
        $this->table('table_1')
            ->dropIndex(new IndexColumn('alias', ['length' => 10]))
            ->save();

        $this->table('table_2')
            ->dropIndex('sorting')
            ->dropForeignKey('t1_fk')
            ->save();
    }

    public function down(): void
    {
        $this->table('table_2')
            ->addIndex('sorting')
            ->addForeignKey('t1_fk', 'table_1', 'id')
            ->save();

        $this->table('table_1')
            ->addIndex(new IndexColumn('alias', ['length' => 10]), 'unique')
            ->save();
    }
}
