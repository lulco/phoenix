<?php

namespace Phoenix\Tests\Migration;

use Phoenix\Migration\AbstractMigration;
use Phoenix\QueryBuilder\Index;

class CreateAndDropTableMigration extends AbstractMigration
{
    protected function up()
    {
        $this->table('test_1')
            ->addColumn('title', 'string')
            ->addColumn('alias', 'string')
            ->addIndex('alias', Index::TYPE_UNIQUE)
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
