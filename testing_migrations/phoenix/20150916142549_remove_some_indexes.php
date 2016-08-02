<?php

namespace Phoenix\MediaLibrary;

use Phoenix\Migration\AbstractMigration;

class RemoveSomeIndexes extends AbstractMigration
{
    public function up()
    {
        $this->table('table_1')
            ->dropIndex('alias')
            ->save();
        
        $this->table('table_2')
            ->dropIndex('sorting')
            ->dropForeignKey('t1_fk')
            ->save();
    }

    public function down()
    {
        $this->table('table_2')
            ->addIndex('sorting')
            ->addForeignKey('t1_fk', 'table_1', 'id')
            ->save();
        
        $this->table('table_1')
            ->addIndex('alias', 'unique')
            ->save();
    }
}
