<?php

namespace Phoenix\Tests\Database\QueryBuilder;

use PDO;
use Phoenix\Database\Adapter\SqliteAdapter;
use Phoenix\Database\Element\Column;
use Phoenix\Database\Element\MigrationTable;
use Phoenix\Database\QueryBuilder\SqliteQueryBuilder;
use Phoenix\Tests\Helpers\Adapter\SqliteCleanupAdapter;
use Phoenix\Tests\Helpers\Pdo\SqlitePdo;
use PHPUnit_Framework_TestCase;

class SqliteQueryBuilderTest extends PHPUnit_Framework_TestCase
{
    private $adapter;

    protected function setUp()
    {
        $pdo = new SqlitePdo();
        $adapter = new SqliteCleanupAdapter($pdo);
        $adapter->cleanupDatabase();

        $pdo = new SqlitePdo();
        $this->adapter = new SqliteAdapter($pdo);
    }

    public function testSimpleCreate()
    {
        $table = new MigrationTable('simple');
        $table->addPrimary(true);
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('title', 'string'));

        $queryBuilder = new SqliteQueryBuilder($this->adapter);
        $expectedQueries = [
            'CREATE TABLE "simple" ("id" integer PRIMARY KEY AUTOINCREMENT NOT NULL,"title" varchar(255) NOT NULL);'
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->createTable($table));
    }

    public function testMoreColumns()
    {
        $table = new MigrationTable('more_columns');
        $table->addPrimary(true);
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('title', 'string'));
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('alias', 'string', ['null' => true]));
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('total', 'integer', ['default' => 0]));
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('bodytext', 'text'));

        $queryBuilder = new SqliteQueryBuilder($this->adapter);
        $expectedQueries = [
            'CREATE TABLE "more_columns" ("id" integer PRIMARY KEY AUTOINCREMENT NOT NULL,"title" varchar(255) NOT NULL,"alias" varchar(255) DEFAULT NULL,"total" integer NOT NULL DEFAULT 0,"bodytext" text NOT NULL);'
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->createTable($table));
    }

    public function testAllTypes()
    {
        $table = new MigrationTable('all_types');
        $table->addPrimary(true);
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('col_uuid', 'uuid'));
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('col_tinyint', 'tinyinteger'));
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('col_smallint', 'smallinteger'));
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('col_mediumint', 'mediuminteger'));
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('col_int', 'integer', ['signed' => false]));
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('col_bigint', 'biginteger'));
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('col_string', 'string'));
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('col_char', 'char'));
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('col_binary', 'binary'));
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('col_varbinary', 'varbinary'));
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('col_tinytext', 'tinytext'));
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('col_mediumtext', 'mediumtext'));
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('col_text', 'text'));
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('col_longtext', 'longtext'));
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('col_tinyblob', 'tinyblob'));
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('col_mediumblob', 'mediumblob'));
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('col_blob', 'blob'));
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('col_longblob', 'longblob'));
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('col_json', 'json'));
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('col_numeric', 'numeric', ['length' => 10, 'decimals' => 3]));
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('col_decimal', 'decimal', ['length' => 10, 'decimals' => 3]));
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('col_float', 'float', ['length' => 10, 'decimals' => 3]));
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('col_double', 'double', ['length' => 10, 'decimals' => 3]));
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('col_boolean', 'boolean'));
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('col_datetime', 'datetime'));
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('col_date', 'date'));
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('col_enum', 'enum', ['values' => ['xxx', 'yyy', 'zzz']]));
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('col_set', 'set', ['values' => ['xxx', 'yyy', 'zzz']]));
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('col_point', 'point', ['null' => true]));
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('col_line', 'line', ['null' => true]));
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('col_polygon', 'polygon', ['null' => true]));

        $queryBuilder = new SqliteQueryBuilder($this->adapter);
        $expectedQueries = [
            'CREATE TABLE "all_types" ("id" integer PRIMARY KEY AUTOINCREMENT NOT NULL,"col_uuid" char(36) NOT NULL,"col_tinyint" tinyinteger NOT NULL,"col_smallint" smallinteger NOT NULL,"col_mediumint" mediuminteger NOT NULL,"col_int" integer NOT NULL,"col_bigint" bigint NOT NULL,"col_string" varchar(255) NOT NULL,"col_char" char(255) NOT NULL,"col_binary" binary(255) NOT NULL,"col_varbinary" varbinary(255) NOT NULL,"col_tinytext" tinytext NOT NULL,"col_mediumtext" mediumtext NOT NULL,"col_text" text NOT NULL,"col_longtext" longtext NOT NULL,"col_tinyblob" tinyblob NOT NULL,"col_mediumblob" mediumblob NOT NULL,"col_blob" blob NOT NULL,"col_longblob" longblob NOT NULL,"col_json" text NOT NULL,"col_numeric" decimal(10,3) NOT NULL,"col_decimal" decimal(10,3) NOT NULL,"col_float" float NOT NULL,"col_double" double NOT NULL,"col_boolean" boolean NOT NULL,"col_datetime" datetime NOT NULL,"col_date" date NOT NULL,"col_enum" enum CHECK(col_enum IN (\'xxx\',\'yyy\',\'zzz\')) NOT NULL,"col_set" enum CHECK(col_set IN (\'xxx\',\'xxx,yyy\',\'xxx,yyy,zzz\',\'xxx,zzz\',\'xxx,zzz,yyy\',\'yyy\',\'yyy,xxx\',\'yyy,xxx,zzz\',\'yyy,zzz\',\'yyy,zzz,xxx\',\'zzz\',\'zzz,xxx\',\'zzz,xxx,yyy\',\'zzz,yyy\',\'zzz,yyy,xxx\')) NOT NULL,"col_point" point DEFAULT NULL,"col_line" varchar(255) DEFAULT NULL,"col_polygon" text DEFAULT NULL);'
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->createTable($table));
    }

    public function testNoPrimaryKey()
    {
        $table = new MigrationTable('no_primary_key');
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('title', 'string', ['null' => true]));
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('total', 'integer', ['default' => 0]));
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('is_deleted', 'boolean', ['default' => false]));

        $queryBuilder = new SqliteQueryBuilder($this->adapter);
        $expectedQueries = [
            'CREATE TABLE "no_primary_key" ("title" varchar(255) DEFAULT NULL,"total" integer NOT NULL DEFAULT 0,"is_deleted" boolean NOT NULL DEFAULT 0);'
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->createTable($table));
    }

    public function testOwnPrimaryKey()
    {
        $table = new MigrationTable('own_primary_key');
        $table->addPrimary(new Column('identifier', 'string', ['length' => 32]));
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('title', 'string', ['default' => '']));

        $queryBuilder = new SqliteQueryBuilder($this->adapter);
        $expectedQueries = [
            'CREATE TABLE "own_primary_key" ("identifier" varchar(32) NOT NULL,"title" varchar(255) NOT NULL,PRIMARY KEY ("identifier"));'
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->createTable($table));
    }

    public function testMoreOwnPrimaryKeys()
    {
        $table = new MigrationTable('more_own_primary_keys');
        $table->addPrimary([new Column('identifier', 'string', ['length' => 32]), new Column('subidentifier', 'string', ['length' => 32])]);
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('title', 'string', ['default' => '']));

        $queryBuilder = new SqliteQueryBuilder($this->adapter);
        $expectedQueries = [
            'CREATE TABLE "more_own_primary_keys" ("identifier" varchar(32) NOT NULL,"subidentifier" varchar(32) NOT NULL,"title" varchar(255) NOT NULL,PRIMARY KEY ("identifier","subidentifier"));'
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->createTable($table));
    }

    public function testOneFieldAsPrimaryKey()
    {
        $table = new MigrationTable('one_field_as_pk');
        $table->addPrimary('identifier');
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('identifier', 'string', ['length' => 32]));
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('title', 'string', ['default' => '']));

        $queryBuilder = new SqliteQueryBuilder($this->adapter);
        $expectedQueries = [
            'CREATE TABLE "one_field_as_pk" ("identifier" varchar(32) NOT NULL,"title" varchar(255) NOT NULL,PRIMARY KEY ("identifier"));'
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->createTable($table));
    }

    public function testMoreFieldsAsPrimaryKeys()
    {
        $table = new MigrationTable('more_fields_as_pk');
        $table->addPrimary(['identifier', 'subidentifier']);
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('identifier', 'string', ['length' => 32]));
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('subidentifier', 'string', ['default' => 'SUB', 'length' => 32]));
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('title', 'string', ['default' => '']));

        $queryBuilder = new SqliteQueryBuilder($this->adapter);
        $expectedQueries = [
            'CREATE TABLE "more_fields_as_pk" ("identifier" varchar(32) NOT NULL,"subidentifier" varchar(32) NOT NULL DEFAULT \'SUB\',"title" varchar(255) NOT NULL,PRIMARY KEY ("identifier","subidentifier"));'
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->createTable($table));
    }

    public function testIndexes()
    {
        $table = new MigrationTable('table_with_indexes');
        $table->addPrimary(true);
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('title', 'string'));
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('alias', 'string'));
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('sorting', 'integer'));
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('bodytext', 'text'));
        $this->assertInstanceOf(MigrationTable::class, $table->addIndex('sorting', '', '', 'table_with_indexes_sorting'));
        $this->assertInstanceOf(MigrationTable::class, $table->addIndex(['title', 'alias'], 'unique', '', 'table_with_indexes_title_alias'));

        $queryBuilder = new SqliteQueryBuilder($this->adapter);
        $expectedQueries = [
            'CREATE TABLE "table_with_indexes" ("id" integer PRIMARY KEY AUTOINCREMENT NOT NULL,"title" varchar(255) NOT NULL,"alias" varchar(255) NOT NULL,"sorting" integer NOT NULL,"bodytext" text NOT NULL);',
            'CREATE INDEX "table_with_indexes_sorting" ON "table_with_indexes" ("sorting");',
            'CREATE UNIQUE INDEX "table_with_indexes_title_alias" ON "table_with_indexes" ("title","alias");',
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->createTable($table));
    }

    public function testForeignKeys()
    {
        $table = new MigrationTable('table_with_foreign_keys');
        $table->addPrimary(true);
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('title', 'string'));
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('alias', 'string'));
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('foreign_table_id', 'integer'));
        $this->assertInstanceOf(MigrationTable::class, $table->addForeignKey('foreign_table_id', 'second_table'));

        $queryBuilder = new SqliteQueryBuilder($this->adapter);
        $expectedQueries = [
            'CREATE TABLE "table_with_foreign_keys" ("id" integer PRIMARY KEY AUTOINCREMENT NOT NULL,"title" varchar(255) NOT NULL,"alias" varchar(255) NOT NULL,"foreign_table_id" integer NOT NULL,CONSTRAINT "table_with_foreign_keys_foreign_table_id" FOREIGN KEY ("foreign_table_id") REFERENCES "second_table" ("id"));'
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->createTable($table));
    }

    public function testIndexesAndForeignKeys()
    {
        $table = new MigrationTable('table_with_indexes_and_foreign_keys');
        $table->addPrimary(true);
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('title', 'string'));
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('alias', 'string'));
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('sorting', 'integer'));
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('bodytext', 'text'));
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('foreign_table_id', 'integer'));
        $this->assertInstanceOf(MigrationTable::class, $table->addForeignKey('foreign_table_id', 'second_table', 'foreign_id', 'set null', 'set null'));
        $this->assertInstanceOf(MigrationTable::class, $table->addIndex('sorting', '', 'btree', 'table_with_indexes_and_foreign_keys_sorting'));
        $this->assertInstanceOf(MigrationTable::class, $table->addIndex(['title', 'alias'], 'unique', '', 'table_with_indexes_and_foreign_keys_title_alias'));

        $queryBuilder = new SqliteQueryBuilder($this->adapter);
        $expectedQueries = [
            'CREATE TABLE "table_with_indexes_and_foreign_keys" ("id" integer PRIMARY KEY AUTOINCREMENT NOT NULL,"title" varchar(255) NOT NULL,"alias" varchar(255) NOT NULL,"sorting" integer NOT NULL,"bodytext" text NOT NULL,"foreign_table_id" integer NOT NULL,CONSTRAINT "table_with_indexes_and_foreign_keys_foreign_table_id" FOREIGN KEY ("foreign_table_id") REFERENCES "second_table" ("foreign_id") ON DELETE SET NULL ON UPDATE SET NULL);',
            'CREATE INDEX "table_with_indexes_and_foreign_keys_sorting" ON "table_with_indexes_and_foreign_keys" ("sorting");',
            'CREATE UNIQUE INDEX "table_with_indexes_and_foreign_keys_title_alias" ON "table_with_indexes_and_foreign_keys" ("title","alias");',
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->createTable($table));
    }

    public function testDropMigrationTable()
    {
        $table = new MigrationTable('drop');

        $queryBuilder = new SqliteQueryBuilder($this->adapter);
        $expectedQueries = [
            'DROP TABLE "drop"'
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->dropTable($table));
    }

    public function testAlterMigrationTable()
    {
        $table = new MigrationTable('add_columns');
        $table->create();
        $this->adapter->getStructure()->update($table);

        // add columns
        $table = new MigrationTable('add_columns');
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('title', 'string'));
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('alias', 'string'));

        $queryBuilder = new SqliteQueryBuilder($this->adapter);

        $timestamp = date('YmdHis');
        $expectedQueries = [
            'ALTER TABLE "add_columns" RENAME TO "_add_columns_old_' . $timestamp . '";',
            'CREATE TABLE "add_columns" ("id" integer PRIMARY KEY AUTOINCREMENT NOT NULL,"title" varchar(255) NOT NULL,"alias" varchar(255) NOT NULL);',
            'INSERT INTO "add_columns" ("id") SELECT "id" FROM "_add_columns_old_' . $timestamp . '"',
            'DROP TABLE "_add_columns_old_' . $timestamp . '"',
        ];

        $this->assertEquals($expectedQueries, $queryBuilder->alterTable($table));
    }

    public function testChangeColumn()
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->query('CREATE TABLE "with_columns_to_change" ("id" integer PRIMARY KEY AUTOINCREMENT NOT NULL,"old_name" integer NOT NULL,"no_name_change" integer NOT NULL);');

        $migartionCreateTable = new MigrationTable('with_columns_to_change');
        $migartionCreateTable->addColumn('old_name', 'integer');
        $migartionCreateTable->addColumn('no_name_change', 'integer');
        $migartionCreateTable->create();
        $this->adapter->getStructure()->update($migartionCreateTable);

        $table = new MigrationTable('with_columns_to_change');
        $this->assertInstanceOf(MigrationTable::class, $table->changeColumn('old_name', 'new_name', 'integer'));
        $this->assertInstanceOf(MigrationTable::class, $table->changeColumn('no_name_change', 'no_name_change', 'integer'));

        $timestamp = date('YmdHis');
        $queryBuilder = new SqliteQueryBuilder($this->adapter);
        $expectedQueries = [
            'ALTER TABLE "with_columns_to_change" RENAME TO "_with_columns_to_change_old_' . $timestamp . '";',
            'CREATE TABLE "with_columns_to_change" ("id" integer PRIMARY KEY AUTOINCREMENT NOT NULL,"new_name" integer NOT NULL,"no_name_change" integer NOT NULL);',
            'INSERT INTO "with_columns_to_change" ("id","new_name","no_name_change") SELECT "id","old_name","no_name_change" FROM "_with_columns_to_change_old_' . $timestamp . '"',
            'DROP TABLE "_with_columns_to_change_old_' . $timestamp . '"',
        ];

        $this->assertEquals($expectedQueries, $queryBuilder->alterTable($table));
    }

    public function testChangeAddedColumn()
    {
        $table = new MigrationTable('with_change_added_column');
        $table->addColumn('title', 'string');
        $table->create();

        $this->adapter->getStructure()->update($table);

        $table = new MigrationTable('with_change_added_column');
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('old_name', 'integer'));
        $this->assertInstanceOf(MigrationTable::class, $table->changeColumn('old_name', 'new_name', 'string'));

        $queryBuilder = new SqliteQueryBuilder($this->adapter);

        $timestamp = date('YmdHis');
        $expectedQueries = [
            'ALTER TABLE "with_change_added_column" RENAME TO "_with_change_added_column_old_' . $timestamp . '";',
            'CREATE TABLE "with_change_added_column" ("id" integer PRIMARY KEY AUTOINCREMENT NOT NULL,"title" varchar(255) NOT NULL,"new_name" varchar(255) NOT NULL);',
            'INSERT INTO "with_change_added_column" ("id","title") SELECT "id","title" FROM "_with_change_added_column_old_' . $timestamp . '"',
            'DROP TABLE "_with_change_added_column_old_' . $timestamp . '"',
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->alterTable($table));
    }

    public function testRenameMigrationTable()
    {
        $table = new MigrationTable('old_table_name');
        $queryBuilder = new SqliteQueryBuilder($this->adapter);
        $expectedQueries = [
            'ALTER TABLE "old_table_name" RENAME TO "new_table_name";',
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->renameTable($table, 'new_table_name'));
    }
}
