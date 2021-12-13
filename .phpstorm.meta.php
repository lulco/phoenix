<?php

namespace PHPSTORM_META
{
    registerArgumentsSet(
        'phoenix_table_primary_key',
        true,
        false,
        'column_name',
        null,
        ['column1_name', 'column2_name'],
        new \Phoenix\Database\Element\Column('column_name', 'column_type'),
        [new \Phoenix\Database\Element\Column('column1_name', 'column1_type'), new \Phoenix\Database\Element\Column('column2_name', 'column2_type')]
    );

    expectedArguments(
        \Phoenix\Migration\AbstractMigration::table(),
        1,
        argumentsSet('phoenix_table_primary_key')
    );

    registerArgumentsSet(
        'phoenix_column_types',
        'integer',
        'string',
        'text',
        'boolean',
        'char',
        'datetime',
        'date',
        'time',
        'timestamp',
        'uuid',
        'json',
        'decimal',
        'numeric',
        'float',
        'double',
        'enum',
        'set',
        'tinyinteger',
        'smallinteger',
        'mediuminteger',
        'biginteger',
        'tinytext',
        'mediumtext',
        'longtext',
        'tinyblob',
        'mediumblob',
        'blob',
        'longblob',
        'binary',
        'varbinary',
        'bit',
        'year',
        'point',
        'line',
        'polygon',
        \Phoenix\Database\Element\Column::TYPE_INTEGER,
        \Phoenix\Database\Element\Column::TYPE_STRING,
        \Phoenix\Database\Element\Column::TYPE_TEXT,
        \Phoenix\Database\Element\Column::TYPE_BOOLEAN,
        \Phoenix\Database\Element\Column::TYPE_CHAR,
        \Phoenix\Database\Element\Column::TYPE_DATETIME,
        \Phoenix\Database\Element\Column::TYPE_DATE,
        \Phoenix\Database\Element\Column::TYPE_TIME,
        \Phoenix\Database\Element\Column::TYPE_TIMESTAMP,
        \Phoenix\Database\Element\Column::TYPE_UUID,
        \Phoenix\Database\Element\Column::TYPE_JSON,
        \Phoenix\Database\Element\Column::TYPE_DECIMAL,
        \Phoenix\Database\Element\Column::TYPE_NUMERIC,
        \Phoenix\Database\Element\Column::TYPE_FLOAT,
        \Phoenix\Database\Element\Column::TYPE_DOUBLE,
        \Phoenix\Database\Element\Column::TYPE_ENUM,
        \Phoenix\Database\Element\Column::TYPE_SET,
        \Phoenix\Database\Element\Column::TYPE_TINY_INTEGER,
        \Phoenix\Database\Element\Column::TYPE_SMALL_INTEGER,
        \Phoenix\Database\Element\Column::TYPE_MEDIUM_INTEGER,
        \Phoenix\Database\Element\Column::TYPE_BIG_INTEGER,
        \Phoenix\Database\Element\Column::TYPE_TINY_TEXT,
        \Phoenix\Database\Element\Column::TYPE_MEDIUM_TEXT,
        \Phoenix\Database\Element\Column::TYPE_LONG_TEXT,
        \Phoenix\Database\Element\Column::TYPE_TINY_BLOB,
        \Phoenix\Database\Element\Column::TYPE_MEDIUM_BLOB,
        \Phoenix\Database\Element\Column::TYPE_BLOB,
        \Phoenix\Database\Element\Column::TYPE_LONG_BLOB,
        \Phoenix\Database\Element\Column::TYPE_BINARY,
        \Phoenix\Database\Element\Column::TYPE_VARBINARY,
        \Phoenix\Database\Element\Column::TYPE_BIT,
        \Phoenix\Database\Element\Column::TYPE_YEAR,
        \Phoenix\Database\Element\Column::TYPE_POINT,
        \Phoenix\Database\Element\Column::TYPE_LINE,
        \Phoenix\Database\Element\Column::TYPE_POLYGON
    );

    expectedArguments(
        \Phoenix\Database\Element\Column::__construct(),
        1,
        argumentsSet('phoenix_column_types')
    );

    expectedArguments(
        \Phoenix\Database\Element\Column::__construct(),
        2,
        argumentsSet('phoenix_column_settings')
    );

    expectedArguments(
        \Phoenix\Database\Element\MigrationTable::addColumn(),
        1,
        argumentsSet('phoenix_column_types')
    );

    registerArgumentsSet('phoenix_column_settings', [
        'null' => true, // default value = false / type = bool
        'default' => null, // default value = null / type = int
        'length' => null, // default value = null / type = int
        'decimals' => null, // default value = null / type = int
        'autoincrement' => false, // default value = false / type = bool
        'signed' => true, // default value = true / type = bool
        'after' => null, // default value = null / type = string
        'first' => false, // default value = false / type = bool
        'charset' => null, // default value = null / type = string
        'collation' => null, // default value = null / type = string
        'comment' => null, // default value = null / type = string
        'values' => null, // default value = null / type = array
    ]);

    expectedArguments(
        \Phoenix\Database\Element\MigrationTable::addColumn(),
        2,
        argumentsSet('phoenix_column_settings')
    );

    expectedArguments(
        \Phoenix\Database\Element\ColumnSettings::__construct(),
        0,
        argumentsSet('phoenix_column_settings')
    );

    registerArgumentsSet('phoenix_index_column_settings', [
        'order' => 'DESC', // default value = ASC / type = string
        'length' => null, // default value = null / type = int
    ]);

    expectedArguments(
        \Phoenix\Database\Element\IndexColumn::__construct(),
        1,
        argumentsSet('phoenix_index_column_settings')
    );

    registerArgumentsSet(
        'phoenix_index_types',
        \Phoenix\Database\Element\Index::TYPE_UNIQUE,
        \Phoenix\Database\Element\Index::TYPE_FULLTEXT,
        \Phoenix\Database\Element\Index::TYPE_NORMAL,
        'UNIQUE',
        'FULLTEXT',
        'unique',
        'fulltext',
        '',
    );

    expectedArguments(
        \Phoenix\Database\Element\Behavior\IndexBehavior::addIndex(),
        1,
        argumentsSet('phoenix_index_types')
    );

    registerArgumentsSet(
        'phoenix_index_methods',
        \Phoenix\Database\Element\Index::METHOD_BTREE,
        \Phoenix\Database\Element\Index::METHOD_HASH,
        \Phoenix\Database\Element\Index::METHOD_DEFAULT,
        'BTREE',
        'HASH',
        'btree',
        'hash',
        ''
    );

    expectedArguments(
        \Phoenix\Database\Element\Behavior\IndexBehavior::addIndex(),
        2,
        argumentsSet('phoenix_index_methods')
    );

    registerArgumentsSet(
        'phoenix_foreign_key_actions',
        \Phoenix\Database\Element\ForeignKey::CASCADE,
        \Phoenix\Database\Element\ForeignKey::RESTRICT,
        \Phoenix\Database\Element\ForeignKey::SET_NULL,
        \Phoenix\Database\Element\ForeignKey::NO_ACTION,
        \Phoenix\Database\Element\ForeignKey::DEFAULT_ACTION,
        'CASCADE',
        'RESTRICT',
        'SET NULL',
        'NO ACTION',
        'cascade',
        'restrict',
        'set null',
        'no action',
        ''
    );

    expectedArguments(
        \Phoenix\Database\Element\Behavior\ForeignKeyBehavior::addForeignKey(),
        3,
        argumentsSet('phoenix_foreign_key_actions')
    );

    expectedArguments(
        \Phoenix\Database\Element\Behavior\ForeignKeyBehavior::addForeignKey(),
        4,
        argumentsSet('phoenix_foreign_key_actions')
    );

    exitPoint(\Phoenix\Database\Adapter\PdoAdapter::throwError());
}
