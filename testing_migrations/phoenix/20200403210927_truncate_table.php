<?php

namespace Phoenix\TestingMigrations;

use Phoenix\Migration\AbstractMigration;

class TruncateTable extends AbstractMigration
{
    protected function up(): void
    {
        $this->table('table_6')->truncate();
    }

    protected function down(): void
    {
        $this->insert('table_6', [
            [
                'title' => 'Item 1',
            ],
            [
                'title' => 'Item 2',
            ],
            [
                'title' => 'Item 3',
            ],
        ]);
    }
}
