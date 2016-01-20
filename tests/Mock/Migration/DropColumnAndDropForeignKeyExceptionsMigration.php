<?php

namespace Phoenix\Tests\Migration;

use Phoenix\Migration\AbstractMigration;

class DropColumnAndDropForeignKeyExceptionsMigration extends AbstractMigration
{
    protected function up()
    {
        $this->dropForeignKey('foreign_key_id');
    }

    protected function down()
    {
        $this->dropColumn('foreign_key_id');
    }
}
