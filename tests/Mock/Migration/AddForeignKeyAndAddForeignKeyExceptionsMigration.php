<?php

namespace Phoenix\Tests\Migration;

use Phoenix\Migration\AbstractMigration;

class AddForeignKeyAndAddForeignKeyExceptionsMigration extends AbstractMigration
{
    protected function up()
    {
        $this->table('test_table')
            ->addColumn('foreign_key_id', 'id')
            ->addForeignKey('foreign_key_id', 'foreign_table');
    }

    protected function down()
    {
        $this->addForeignKey('foreign_key_id', 'foreign_table');
    }
}
