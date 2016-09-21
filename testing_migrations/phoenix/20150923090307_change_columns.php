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

        // TODO otestovat co sa stane ked zmazeme nejaku value z enum / setu, ktora sa pouziva - asi by to malo hodit restrikciu ale mam pocit ze mysql na to dlabe
        $this->table('all_types')
            ->changeColumn('col_enum', 'col_enum', 'enum', ['values' => ['xxx', 'yyy', 'zzz', 'qqq'], 'null' => true])
            ->changeColumn('col_set', 'col_set', 'set', ['values' => ['xxx', 'yyy', 'zzz', 'qqq'], 'null' => true])
            ->save();
    }

    public function down()
    {
        $this->table('all_types')
            ->changeColumn('col_enum', 'col_enum', 'enum', ['values' => ['xxx', 'yyy', 'zzz'], 'null' => true])
            ->changeColumn('col_set', 'col_set', 'set', ['values' => ['xxx', 'yyy', 'zzz'], 'null' => true])
            ->save();

        $this->table('table_2')
            ->changeColumn('new_sorting', 'sorting', 'integer')
            ->save();

        $this->table('table_2')
            ->dropIndex('sorting')
            ->save();
    }
}
