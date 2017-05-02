<?php

namespace Phoenix\TestingMigrations;

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

        $this->table('all_types')
            ->addColumn('new_column', 'string', ['null' => true])
            ->changeColumn('col_enum', 'col_enum', 'enum', ['values' => ['xxx', 'yyy', 'zzz', 'qqq'], 'null' => true])
            ->changeColumn('col_set', 'col_set', 'set', ['values' => ['xxx', 'yyy', 'zzz', 'qqq'], 'null' => true])
            ->save();

        $this->table('all_types')
            ->dropColumn('new_column')
            ->save();
    }

    public function down()
    {
        $this->table('all_types')
            ->addColumn('new_column', 'string', ['null' => true])
            ->save();

        $this->table('all_types')
            ->dropColumn('new_column')
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
