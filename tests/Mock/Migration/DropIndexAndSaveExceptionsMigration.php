<?php

namespace Phoenix\Tests\Migration;

use Phoenix\Migration\AbstractMigration;

class DropIndexAndSaveExceptionsMigration extends AbstractMigration
{
    protected function up()
    {
        $this->dropIndex('foreign_key_id');
    }

    protected function down()
    {
        $this->save();
    }
}
