<?php

namespace Phoenix\TestingMigrations;

use Phoenix\Database\Element\Column;
use Phoenix\Migration\AbstractMigration;
use Ramsey\Uuid\Uuid;

class AddPrimaryColumns extends AbstractMigration
{
    public function up(): void
    {
        $this->table('table_3')
            ->addPrimaryColumns([new Column('id', 'integer', ['autoincrement' => true])])
            ->save();

        $this->table('table_4')
            ->addPrimaryColumns([new Column('identifier', 'uuid')], function (array $data) {
                $data['identifier'] = (string) Uuid::uuid4();
                return $data;
            })
            ->save();
    }

    public function down(): void
    {
        $this->table('table_4')
            ->dropColumn('identifier')
            ->save();

        $this->table('table_3')
            ->dropColumn('id')
            ->save();
    }
}
