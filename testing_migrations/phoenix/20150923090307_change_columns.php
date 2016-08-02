<?php

namespace Phoenix\MediaLibrary;

use Phoenix\Migration\AbstractMigration;

class ChangeColumns extends AbstractMigration
{
    public function up()
    {
        $this->table('table_2')
            ->addIndex('sorting')
            ->save();

        $this->table('table_2')
            ->changeColumn('sorting', 'new_sorting', 'integer')
            ->save();
    }
    
    public function down()
    {
        $this->table('table_2')
            ->changeColumn('new_sorting', 'sorting', 'integer')
            ->save();
        
        $this->table('table_2')
            ->dropIndex('sorting')
            ->save();
    }
}
