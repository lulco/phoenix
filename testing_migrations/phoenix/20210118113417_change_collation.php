<?php

namespace Phoenix\TestingMigrations;

use Phoenix\Migration\AbstractMigration;

class ChangeCollation extends AbstractMigration
{
    protected function up(): void
    {
        $this->changeCollation('utf8mb4_general_ci');

        $this->insert('renamed_table_1', [
            'id' => 1000,
            'title' => 'Panda 🐼',
            'alias' => 'panda',
        ]);
    }

    protected function down(): void
    {
        $this->delete('renamed_table_1', ['id' => 1000]);
        $this->changeCollation('utf8_general_ci');
    }
}
