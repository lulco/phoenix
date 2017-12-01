<?php

namespace Phoenix\TestingMigrations;

use Phoenix\Migration\AbstractMigration;

class RenameTables extends AbstractMigration
{
    public function up(): void
    {
        $this->table('table_1')->rename('renamed_table_1');
    }

    protected function down(): void
    {
        $this->table('renamed_table_1')->rename('table_1');
    }
}
