<?php

namespace Phoenix\TestingMigrations;

use Nette\Utils\DateTime;
use Phoenix\Migration\AbstractMigration;

class AddDataAfterChanges extends AbstractMigration
{
    public function up()
    {
        $this->insert('all_types', [
            'identifier' => '914dbcc3-3b19-4b17-863b-2ce37a63465e',
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
            'col_enum' => 'qqq',
            'col_set' => ['yyy', 'qqq'],
        ]);
    }

    protected function down()
    {
        $this->delete('all_types', ['identifier' => '914dbcc3-3b19-4b17-863b-2ce37a63465e']);
    }
}
