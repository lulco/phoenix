<?php

namespace Phoenix\TestingMigrations;

use Phoenix\Migration\AbstractMigration;

class SetAutoIncrement extends AbstractMigration
{
    protected function up(): void
    {
        $this->table('renamed_table_1')->setAutoIncrement(100);

        $this->insert('renamed_table_1', [
            [
                'title' => 'Item #100',
                'alias' => 'item-100',
            ],
            [
                'title' => 'Item #101',
                'alias' => 'item-101',
            ],
        ]);
    }

    protected function down(): void
    {
        $this->delete('renamed_table_1', ['id' => [100, 101]]);
        $this->table('renamed_table_1')->setAutoIncrement(100);
    }
}
