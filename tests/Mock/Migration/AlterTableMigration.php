<?php

namespace Phoenix\Tests\Migration;

use Phoenix\Migration\AbstractMigration;
use Phoenix\QueryBuilder\Index;

class AlterTableMigration extends AbstractMigration
{
    protected $useTransaction = true;
    
    protected function up()
    {
        $this->table('test_table')
            ->addColumn('title', 'string')
            ->addColumn('alias', 'string')
            ->addColumn('foreign_key_id', 'integer')
            ->addIndex('alias', Index::TYPE_UNIQUE)
            ->addForeignKey('foreign_key_id', 'foreign_table')
            ->save();
    }

    protected function down()
    {
        $this->table('test_table')
            ->dropColumn('title')
            ->dropColumn('alias')
            ->dropColumn('foreign_key_id', 'integer')
            ->dropIndex('alias', Index::TYPE_UNIQUE)
            ->dropForeignKey('foreign_key_id', 'foreign_table')
            ->save();
    }
}
