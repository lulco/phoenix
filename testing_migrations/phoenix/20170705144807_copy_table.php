<?php

namespace Phoenix\TestingMigrations;

use Phoenix\Database\Element\MigrationTable;
use Phoenix\Migration\AbstractMigration;

class CopyTables extends AbstractMigration
{
    public function up()
    {
        $this->table('table_2')->copy('new_table_2');
        $this->table('table_3')->copy('new_table_3', MigrationTable::COPY_STRUCTURE_AND_DATA);
        $this->table('table_2')->copy('new_table_2', MigrationTable::COPY_ONLY_DATA);
    }

    protected function down()
    {
        $this->table('new_table_3')->drop();
        $this->table('new_table_2')->drop();
    }
}
