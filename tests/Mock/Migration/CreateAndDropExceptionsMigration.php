<?php

namespace Phoenix\Tests\Migration;

use Phoenix\Migration\AbstractMigration;

class CreateAndDropExceptionsMigration extends AbstractMigration
{
    protected function up()
    {
        $this->create();
    }

    protected function down()
    {
        $this->drop();
    }
}
