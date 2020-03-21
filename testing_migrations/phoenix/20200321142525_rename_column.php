<?php

namespace Phoenix\TestingMigrations;

use Phoenix\Migration\AbstractMigration;

class RenameColumn extends AbstractMigration
{
    public function up(): void
    {
        $this->table('table_4')->renameColumn('identifier', 'id')->save();
    }

    protected function down(): void
    {
        $this->table('table_4')->renameColumn('id', 'identifier')->save();
    }
}
