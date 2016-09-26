<?php

namespace Phoenix\TestingMigrations;

use Nette\Utils\DateTime;
use Phoenix\Migration\AbstractMigration;

class AddData extends AbstractMigration
{
    public function up()
    {
        $this->insert('table_1', [
            'id' => 1,
            'title' => 'First item',
            'alias' => 'first-item',
        ]);

        $this->insert('table_1', [
            [
                'id' => 2,
                'title' => 'Second item',
                'alias' => 'second-item',
            ],
            [
                'id' => 3,
                'title' => 'Third item',
                'alias' => 'third-item',
            ]
        ]);

        $this->insert('table_2', [
            'id' => 1,
            'title' => 'T2 First item',
            'sorting' => 1,
            't1_fk' => 1,
            'created_at' => new DateTime(),
        ]);

        $this->insert('table_2', [
            'id' => 2,
            'title' => 'T2 Second item',
            'sorting' => 2,
            't1_fk' => 3,
            'created_at' => new DateTime(),
        ]);

        $this->insert('table_3', [
            'identifier' => '6fedffa4-897e-41b1-ba00-185b7c1726d2',
            't1_fk' => 3,
        ]);

        $this->insert('table_3', [
            'identifier' => '914dbcc3-3b19-4b17-863b-2ce37a63465b',
            't1_fk' => 1,
            't2_fk' => 1,
        ]);

        $this->update('table_1', [
            'title' => 'Renamed second item'
        ], ['id' => 2]);

        $this->insert('all_types', [
            'identifier' => '914dbcc3-3b19-4b17-863b-2ce37a63465c',
            'col_integer' => 50,
            'col_bigint' => 1234567890,
            'col_string' => 'string',
            'col_char' => 'char',
            'col_text' => 'text',
            'col_json' => json_encode(['json' => 'my json']),
            'col_float' => 3.1415,
            'col_decimal' => 3.1415,
            'col_boolean' => true,
            'col_datetime' => new DateTime(),
            'col_date' => (new DateTime())->format('Y-m-d'),
            'col_enum' => 'xxx',
            'col_set' => ['yyy', 'zzz'],
        ]);

        $this->insert('all_types', [
            'identifier' => '914dbcc3-3b19-4b17-863b-2ce37a63465d',
            'col_integer' => 150,
            'col_bigint' => 9876543210,
            'col_string' => 'string',
            'col_char' => 'char',
            'col_text' => 'text',
            'col_json' => json_encode(['json' => 'my new json']),
            'col_float' => 3.1415,
            'col_decimal' => 3.1415,
            'col_boolean' => true,
            'col_datetime' => new DateTime(),
            'col_date' => (new DateTime())->format('Y-m-d'),
        ]);
    }

    protected function down()
    {
        $this->delete('all_types', ['identifier' => '914dbcc3-3b19-4b17-863b-2ce37a63465d']);
        $this->delete('all_types', ['identifier' => '914dbcc3-3b19-4b17-863b-2ce37a63465c']);
        $this->delete('table_3', ['identifier' => '914dbcc3-3b19-4b17-863b-2ce37a63465b']);
        $this->delete('table_3', ['identifier' => '6fedffa4-897e-41b1-ba00-185b7c1726d2']);
        $this->delete('table_2', ['id' => 2]);
        $this->delete('table_2', ['id' => 1]);
        $this->delete('table_1', ['id' => 1]);
        $this->delete('table_1', ['id' => 2]);
        $this->delete('table_1', ['id' => 3]);
    }
}
