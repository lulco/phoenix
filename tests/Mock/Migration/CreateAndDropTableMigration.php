<?php

namespace Phoenix\Tests\Mock\Migration;

use Phoenix\Migration\AbstractMigration;
use Phoenix\Database\Element\Index;

class CreateAndDropTableMigration extends AbstractMigration
{
    protected function up()
    {
        $this->table('test_1')
            ->addColumn('title', 'string')
            ->addColumn('alias', 'string')
            ->addColumn('foreign_key_id', 'integer')
            ->addIndex('alias', Index::TYPE_UNIQUE)
            ->addForeignKey('foreign_key_id', 'foreign_table')
            ->create();
        
        $this->table('test_2')
            ->drop();
    }

    protected function down()
    {
        $this->table('test_1')
            ->drop();
        
        $this->table('test_2')
            ->create();
    }
}
