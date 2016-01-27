<?php

namespace Phoenix\Tests\Mock\Migration;

use Phoenix\Migration\AbstractMigration;

class UseTransactionMigration extends AbstractMigration
{
    protected $useTransaction = true;
    
    protected function up()
    {
        $this->execute('INSERT INTO test_table VALUES (10, "title", "alias")');
        $this->execute('INSERT INTO test_table VALUES (20, "title", "alias")');
    }

    protected function down()
    {
        $this->table('test_table')
            ->addColumn('title', 'string')
            ->addColumn('alias', 'string')
            ->create();
        
        $this->execute('INSERT INTO test_table VALUES (1, "title", "alias")');
        $this->execute('INSERT INTO test_table VALUES (1, "title", "alias")');  // duplicite id will throw exception
    }
}
