<?php

namespace Phoenix\TestingMigrations;

use Phoenix\Database\Element\Column;
use Phoenix\Migration\AbstractMigration;

class AddPrimaryColumns extends AbstractMigration
{
    public function up(): void
    {
        $this->table('table_3')
            ->addPrimaryColumns([new Column('id', 'integer', ['autoincrement' => true])])
            ->save();
    }

    public function down(): void
    {
        $this->table('table_3')
            ->dropColumn('id')
            ->save();
    }
}
