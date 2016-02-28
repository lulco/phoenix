<?php

namespace Phoenix\Tests\Mock\Migration;

use Phoenix\Migration\AbstractMigration;

class RenameTableAndChangeColumnsMigration extends AbstractMigration
{
    protected function up()
    {
        $this->table('test_table')
            ->rename('new_test_table');
        
        $this->table('new_test_table')
            ->rename('test_table');
    }

    protected function down()
    {
        $this->table('test_table')
            ->changeColumn('title', 'new_title', 'string')
            ->changeColumn('alias', new \Phoenix\Database\Element\Column('alias', 'string'))
            ->save();
        
        $this->table('test_table')
            ->changeColumn('new_title', 'title', 'string')
            ->changeColumn('alias', new \Phoenix\Database\Element\Column('alias', 'string'))
            ->save();
    }
}
