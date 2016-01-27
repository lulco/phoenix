<?php

namespace Phoenix\Tests\Mock\Migration;

use Phoenix\Migration\AbstractMigration;

class DoubleUseOfTableExceptionMigration extends AbstractMigration
{
    protected function up()
    {
        $this->table('first')
            ->addColumn('title', 'string');
        
        $this->table('second');
    }

    protected function down()
    {
        $this->table('first')
            ->create();
            
        
        $this->table('second')
            ->addColumn('title', 'string');
        
        $this->table('third');
    }
}
