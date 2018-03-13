<?php

namespace Phoenix\TestingMigrations;

use Phoenix\Migration\AbstractMigration;

class AddColumn extends AbstractMigration
{
    public function up(): void
    {
        $this->table('all_types')
            ->addColumn('new_column', 'string', ['null' => true])
            ->save();
    }

    public function down(): void
    {
        $this->table('all_types')
            ->dropColumn('new_column')
            ->save();
    }
}
