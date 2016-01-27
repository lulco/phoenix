<?php

namespace Phoenix\Tests\Mock\Migration;

use Phoenix\Migration\AbstractMigration;

class AddColumnAndAddIndexExceptionsMigration extends AbstractMigration
{
    protected function up()
    {
        $this->addColumn('title', 'string');
    }

    protected function down()
    {
        $this->addIndex('test', 'unique');
    }
}
