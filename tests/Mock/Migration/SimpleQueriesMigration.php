<?php

namespace Phoenix\Tests\Mock\Migration;

use Phoenix\Migration\AbstractMigration;

class SimpleQueriesMigration extends AbstractMigration
{
    protected function up()
    {
        $this->execute('SELECT * FROM test_table');
    }

    protected function down()
    {
        $this->execute('SELECT * FROM test_table');
    }
}
