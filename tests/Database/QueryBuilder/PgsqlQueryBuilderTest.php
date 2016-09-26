<?php

namespace Phoenix\Tests\Database\QueryBuilder;

use Phoenix\Database\Element\Column;
use Phoenix\Database\Element\ForeignKey;
use Phoenix\Database\Element\Index;
use Phoenix\Database\Element\Table;
use Phoenix\Database\QueryBuilder\PgsqlQueryBuilder;
use PHPUnit_Framework_TestCase;

class PgsqlQueryBuilderTest extends PHPUnit_Framework_TestCase
{
    public function testUnsupportedColumnType()
    {
        $table = new Table('unsupported');
        $table->addPrimary(true);
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('title', 'unsupported')));

        $queryBuilder = new PgsqlQueryBuilder();
        $this->setExpectedException('\Exception', 'Type "unsupported" is not allowed');
        $queryBuilder->createTable($table);
    }

    public function testSimpleCreate()
    {
        $table = new Table('simple');
        $table->addPrimary(true);
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('title', 'string')));

        $queryBuilder = new PgsqlQueryBuilder();
        $expectedQueries = [
            'CREATE SEQUENCE "simple_seq";',
            'CREATE TABLE "simple" ("id" int4 DEFAULT nextval(\'simple_seq\'::regclass) NOT NULL,"title" varchar(255) NOT NULL,CONSTRAINT "simple_pkey" PRIMARY KEY ("id"));'
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->createTable($table));
    }

    public function testMoreColumns()
    {
        $table = new Table('more_columns');
        $table->addPrimary(true);
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('title', 'string')));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('alias', 'string', ['null' => true])));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('total', 'integer', ['default' => 0])));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('bodytext', 'text')));

        $queryBuilder = new PgsqlQueryBuilder();
        $expectedQueries = [
            'CREATE SEQUENCE "more_columns_seq";',
            'CREATE TABLE "more_columns" ("id" int4 DEFAULT nextval(\'more_columns_seq\'::regclass) NOT NULL,"title" varchar(255) NOT NULL,"alias" varchar(255) DEFAULT NULL,"total" int4 DEFAULT 0 NOT NULL,"bodytext" text NOT NULL,CONSTRAINT "more_columns_pkey" PRIMARY KEY ("id"));',
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->createTable($table));
    }

    public function testAllTypes()
    {
        $table = new Table('all_types');
        $table->addPrimary(true);
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('col_uuid', 'uuid')));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('col_int', 'integer')));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('col_bigint', 'biginteger')));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('col_string', 'string')));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('col_char', 'char')));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('col_text', 'text')));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('col_json', 'json')));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('col_float', 'float', ['length' => 10, 'decimals' => 3])));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('col_decimal', 'decimal', ['length' => 10, 'decimals' => 3])));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('col_boolean', 'boolean')));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('col_datetime', 'datetime')));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('col_date', 'date')));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('col_enum', 'enum', ['values' => ['xxx', 'yyy', 'zzz']])));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('col_set', 'set', ['values' => ['xxx', 'yyy', 'zzz']])));

        $queryBuilder = new PgsqlQueryBuilder();
        $expectedQueries = [
            'CREATE TYPE "all_types__col_enum" AS ENUM (\'xxx\',\'yyy\',\'zzz\');',
            'CREATE TYPE "all_types__col_set" AS ENUM (\'xxx\',\'yyy\',\'zzz\');',
            'CREATE SEQUENCE "all_types_seq";',
            'CREATE TABLE "all_types" ("id" int4 DEFAULT nextval(\'all_types_seq\'::regclass) NOT NULL,"col_uuid" uuid NOT NULL,"col_int" int4 NOT NULL,"col_bigint" int8 NOT NULL,"col_string" varchar(255) NOT NULL,"col_char" char(255) NOT NULL,"col_text" text NOT NULL,"col_json" json NOT NULL,"col_float" real NOT NULL,"col_decimal" decimal(10,3) NOT NULL,"col_boolean" bool NOT NULL,"col_datetime" timestamp(6) NOT NULL,"col_date" date NOT NULL,"col_enum" all_types__col_enum NOT NULL,"col_set" all_types__col_set[] NOT NULL,CONSTRAINT "all_types_pkey" PRIMARY KEY ("id"));'
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->createTable($table));
    }

    public function testNoPrimaryKey()
    {
        $table = new Table('no_primary_key');
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('title', 'string', ['null' => true])));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('total', 'integer', ['default' => 0])));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('is_deleted', 'boolean', ['default' => false])));

        $queryBuilder = new PgsqlQueryBuilder();
        $expectedQueries = [
            'CREATE TABLE "no_primary_key" ("title" varchar(255) DEFAULT NULL,"total" int4 DEFAULT 0 NOT NULL,"is_deleted" bool DEFAULT false NOT NULL);'
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->createTable($table));
    }

    public function testOwnPrimaryKey()
    {
        $table = new Table('own_primary_key');
        $table->addPrimary(new Column('identifier', 'string', ['length' => 32]));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('title', 'string', ['default' => ''])));

        $queryBuilder = new PgsqlQueryBuilder();
        $expectedQueries = [
            'CREATE TABLE "own_primary_key" ("identifier" varchar(32) NOT NULL,"title" varchar(255) DEFAULT \'\' NOT NULL,CONSTRAINT "own_primary_key_pkey" PRIMARY KEY ("identifier"));'
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->createTable($table));
    }

    public function testMoreOwnPrimaryKeys()
    {
        $table = new Table('more_own_primary_keys');
        $table->addPrimary([new Column('identifier', 'string', ['length' => 32]), new Column('subidentifier', 'string', ['length' => 32])]);
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('title', 'string', ['default' => ''])));

        $queryBuilder = new PgsqlQueryBuilder();
        $expectedQueries = [
            'CREATE TABLE "more_own_primary_keys" ("identifier" varchar(32) NOT NULL,"subidentifier" varchar(32) NOT NULL,"title" varchar(255) DEFAULT \'\' NOT NULL,CONSTRAINT "more_own_primary_keys_pkey" PRIMARY KEY ("identifier","subidentifier"));'
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->createTable($table));
    }

    public function testOneFieldAsPrimaryKey()
    {
        $table = new Table('one_field_as_pk');
        $table->addPrimary('identifier');
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('identifier', 'string', ['length' => 32])));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('title', 'string', ['default' => ''])));

        $queryBuilder = new PgsqlQueryBuilder();
        $expectedQueries = [
            'CREATE TABLE "one_field_as_pk" ("identifier" varchar(32) NOT NULL,"title" varchar(255) DEFAULT \'\' NOT NULL,CONSTRAINT "one_field_as_pk_pkey" PRIMARY KEY ("identifier"));',
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->createTable($table));
    }

    public function testMoreFieldsAsPrimaryKeys()
    {
        $table = new Table('more_fields_as_pk');
        $table->addPrimary(['identifier', 'subidentifier']);
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('identifier', 'string', ['length' => 32])));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('subidentifier', 'string', ['length' => 32])));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('title', 'string', ['default' => ''])));

        $queryBuilder = new PgsqlQueryBuilder();
        $expectedQueries = [
            'CREATE TABLE "more_fields_as_pk" ("identifier" varchar(32) NOT NULL,"subidentifier" varchar(32) NOT NULL,"title" varchar(255) DEFAULT \'\' NOT NULL,CONSTRAINT "more_fields_as_pk_pkey" PRIMARY KEY ("identifier","subidentifier"));'
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->createTable($table));
    }

    public function testIndexes()
    {
        $table = new Table('table_with_indexes');
        $table->addPrimary(true);
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('title', 'string')));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('alias', 'string')));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('sorting', 'integer')));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('bodytext', 'text')));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addIndex(new Index('sorting', 'table_with_indexes_sorting', '', 'btree')));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addIndex(new Index(['title', 'alias'], 'table_with_indexes_title_alias', 'unique')));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addIndex(new Index('bodytext', 'table_with_indexes_bodytext', 'fulltext', 'hash')));

        $queryBuilder = new PgsqlQueryBuilder();
        $expectedQueries = [
            'CREATE SEQUENCE "table_with_indexes_seq";',
            'CREATE TABLE "table_with_indexes" ("id" int4 DEFAULT nextval(\'table_with_indexes_seq\'::regclass) NOT NULL,"title" varchar(255) NOT NULL,"alias" varchar(255) NOT NULL,"sorting" int4 NOT NULL,"bodytext" text NOT NULL,CONSTRAINT "table_with_indexes_pkey" PRIMARY KEY ("id"));',
            'CREATE INDEX "table_with_indexes_sorting" ON "table_with_indexes" USING BTREE ("sorting");',
            'CREATE UNIQUE INDEX "table_with_indexes_title_alias" ON "table_with_indexes" ("title","alias");',
            'CREATE FULLTEXT INDEX "table_with_indexes_bodytext" ON "table_with_indexes" USING HASH ("bodytext");',
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->createTable($table));
    }

    public function testForeignKeys()
    {
        $table = new Table('table_with_foreign_keys');
        $table->addPrimary(true);
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('title', 'string')));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('alias', 'string')));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('foreign_table_id', 'integer')));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addForeignKey(new ForeignKey('foreign_table_id', 'second_table')));

        $queryBuilder = new PgsqlQueryBuilder();
        $expectedQueries = [
            'CREATE SEQUENCE "table_with_foreign_keys_seq";',
            'CREATE TABLE "table_with_foreign_keys" ("id" int4 DEFAULT nextval(\'table_with_foreign_keys_seq\'::regclass) NOT NULL,"title" varchar(255) NOT NULL,"alias" varchar(255) NOT NULL,"foreign_table_id" int4 NOT NULL,CONSTRAINT "table_with_foreign_keys_pkey" PRIMARY KEY ("id"),CONSTRAINT "table_with_foreign_keys_foreign_table_id" FOREIGN KEY ("foreign_table_id") REFERENCES "second_table" ("id"));'
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->createTable($table));
    }

    public function testIndexesAndForeignKeys()
    {
        $table = new Table('table_with_indexes_and_foreign_keys');
        $table->addPrimary(true);
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('title', 'string')));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('alias', 'string')));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('sorting', 'integer')));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('bodytext', 'text')));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('foreign_table_id', 'integer')));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addForeignKey(new ForeignKey('foreign_table_id', 'second_table', 'foreign_id', 'set null', 'set null')));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addIndex(new Index('sorting', 'table_with_indexes_and_foreign_keys_sorting', '', 'btree')));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addIndex(new Index(['title', 'alias'], 'table_with_indexes_and_foreign_keys_title_alias', 'unique')));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addIndex(new Index('bodytext', 'table_with_indexes_and_foreign_keys_bodytext', 'fulltext', 'hash')));

        $queryBuilder = new PgsqlQueryBuilder();
        $expectedQueries = [
            'CREATE SEQUENCE "table_with_indexes_and_foreign_keys_seq";',
            'CREATE TABLE "table_with_indexes_and_foreign_keys" ("id" int4 DEFAULT nextval(\'table_with_indexes_and_foreign_keys_seq\'::regclass) NOT NULL,"title" varchar(255) NOT NULL,"alias" varchar(255) NOT NULL,"sorting" int4 NOT NULL,"bodytext" text NOT NULL,"foreign_table_id" int4 NOT NULL,CONSTRAINT "table_with_indexes_and_foreign_keys_pkey" PRIMARY KEY ("id"),CONSTRAINT "table_with_indexes_and_foreign_keys_foreign_table_id" FOREIGN KEY ("foreign_table_id") REFERENCES "second_table" ("foreign_id") ON DELETE SET NULL ON UPDATE SET NULL);',
            'CREATE INDEX "table_with_indexes_and_foreign_keys_sorting" ON "table_with_indexes_and_foreign_keys" USING BTREE ("sorting");',
            'CREATE UNIQUE INDEX "table_with_indexes_and_foreign_keys_title_alias" ON "table_with_indexes_and_foreign_keys" ("title","alias");',
            'CREATE FULLTEXT INDEX "table_with_indexes_and_foreign_keys_bodytext" ON "table_with_indexes_and_foreign_keys" USING HASH ("bodytext");',
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->createTable($table));
    }

    public function testDropTable()
    {
        $table = new Table('drop');
        $queryBuilder = new PgsqlQueryBuilder();
        $expectedQueries = [
            'DROP TABLE "drop";',
            'DROP SEQUENCE IF EXISTS "drop_seq";',
            'DELETE FROM "pg_type" WHERE "typname" LIKE \'drop__%\';',
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->dropTable($table));
    }

    public function testAlterTable()
    {
        // add columns
        $table = new Table('add_columns');
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('title', 'string')));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('alias', 'string')));

        $queryBuilder = new PgsqlQueryBuilder();
        $expectedQueries = [
            'ALTER TABLE "add_columns" ADD COLUMN "title" varchar(255) NOT NULL,ADD COLUMN "alias" varchar(255) NOT NULL;'
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->alterTable($table));

        // add and remove primary key
        $table = new Table('change_primary_key');
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->dropPrimaryKey());
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addPrimary('new_primary'));

        $queryBuilder = new PgsqlQueryBuilder();
        $expectedQueries = [
            'ALTER TABLE "change_primary_key" DROP CONSTRAINT "change_primary_key_pkey";',
            'ALTER TABLE "change_primary_key" ADD CONSTRAINT "change_primary_key_pkey" PRIMARY KEY ("new_primary");',
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->alterTable($table));

        // add index
        $table = new Table('add_index');
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addIndex(new Index('alias', 'add_index_alias', 'unique')));

        $queryBuilder = new PgsqlQueryBuilder();
        $expectedQueries = [
            'CREATE UNIQUE INDEX "add_index_alias" ON "add_index" ("alias");',
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->alterTable($table));

        // add column and index
        $table = new Table('add_column_and_index');
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('alias', 'string')));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addIndex(new Index('alias', 'add_column_and_index_alias', 'unique')));

        $queryBuilder = new PgsqlQueryBuilder();
        $expectedQueries = [
            'ALTER TABLE "add_column_and_index" ADD COLUMN "alias" varchar(255) NOT NULL;',
            'CREATE UNIQUE INDEX "add_column_and_index_alias" ON "add_column_and_index" ("alias");',
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->alterTable($table));

        // add foreign key, index, columns
        $table = new Table('add_columns_index_foreign_key');
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('foreign_key_id', 'integer')));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('sorting', 'integer')));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addIndex(new Index('sorting', 'add_columns_index_foreign_key_sorting')));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addForeignKey(new ForeignKey('foreign_key_id', 'referenced_table')));

        $queryBuilder = new PgsqlQueryBuilder();
        $expectedQueries = [
            'ALTER TABLE "add_columns_index_foreign_key" ADD COLUMN "foreign_key_id" int4 NOT NULL,ADD COLUMN "sorting" int4 NOT NULL;',
            'CREATE INDEX "add_columns_index_foreign_key_sorting" ON "add_columns_index_foreign_key" ("sorting");',
            'ALTER TABLE "add_columns_index_foreign_key" ADD CONSTRAINT "add_columns_index_foreign_key_foreign_key_id" FOREIGN KEY ("foreign_key_id") REFERENCES "referenced_table" ("id");',
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->alterTable($table));

        // remove columns

        // remove index

        // remove foreign key

        // combination of add / remove column, add / remove index, add / remove foreign key
        $table = new Table('all_in_one');
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('foreign_key_id', 'integer')));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('sorting', 'integer')));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->dropColumn('title'));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addIndex(new Index('sorting', 'all_in_one_sorting')));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->dropIndex('all_in_one_alias'));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addForeignKey(new ForeignKey('foreign_key_id', 'referenced_table')));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->dropForeignKey('foreign_key_to_drop_id'));

        $queryBuilder = new PgsqlQueryBuilder();
        $expectedQueries = [
            'DROP INDEX "all_in_one_alias";',
            'ALTER TABLE "all_in_one" DROP CONSTRAINT "all_in_one_foreign_key_to_drop_id";',
            'ALTER TABLE "all_in_one" DROP COLUMN "title";',
            'ALTER TABLE "all_in_one" ADD COLUMN "foreign_key_id" int4 NOT NULL,ADD COLUMN "sorting" int4 NOT NULL;',
            'CREATE INDEX "all_in_one_sorting" ON "all_in_one" ("sorting");',
            'ALTER TABLE "all_in_one" ADD CONSTRAINT "all_in_one_foreign_key_id" FOREIGN KEY ("foreign_key_id") REFERENCES "referenced_table" ("id");',
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->alterTable($table));

        // mixed order of calls add / remove column, add / remove index, add / remove foreign key - output is the same
        $table = new Table('all_in_one_mixed');
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addIndex(new Index('sorting', 'all_in_one_mixed_sorting')));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->dropForeignKey('foreign_key_to_drop_id'));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('foreign_key_id', 'integer')));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->dropColumn('title'));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('sorting', 'integer')));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->dropIndex('all_in_one_mixed_alias'));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addForeignKey(new ForeignKey('foreign_key_id', 'referenced_table')));

        $queryBuilder = new PgsqlQueryBuilder();
        $expectedQueries = [
            'DROP INDEX "all_in_one_mixed_alias";',
            'ALTER TABLE "all_in_one_mixed" DROP CONSTRAINT "all_in_one_mixed_foreign_key_to_drop_id";',
            'ALTER TABLE "all_in_one_mixed" DROP COLUMN "title";',
            'ALTER TABLE "all_in_one_mixed" ADD COLUMN "foreign_key_id" int4 NOT NULL,ADD COLUMN "sorting" int4 NOT NULL;',
            'CREATE INDEX "all_in_one_mixed_sorting" ON "all_in_one_mixed" ("sorting");',
            'ALTER TABLE "all_in_one_mixed" ADD CONSTRAINT "all_in_one_mixed_foreign_key_id" FOREIGN KEY ("foreign_key_id") REFERENCES "referenced_table" ("id");',
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->alterTable($table));
    }

    public function testChangeColumn()
    {
        $table = new Table('with_columns_to_change');
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->changeColumn('old_name', new Column('new_name', 'integer')));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->changeColumn('no_name_change', new Column('no_name_change', 'integer')));

        $queryBuilder = new PgsqlQueryBuilder();
        $expectedQueries = [
            'ALTER TABLE "with_columns_to_change" RENAME COLUMN "old_name" TO "new_name";',
            'ALTER TABLE "with_columns_to_change" ALTER COLUMN "new_name" TYPE int4 USING new_name::integer;',
            'ALTER TABLE "with_columns_to_change" ALTER COLUMN "no_name_change" TYPE int4 USING no_name_change::integer;',
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->alterTable($table));
    }

    public function testRenameTable()
    {
        $table = new Table('old_table_name');
        $queryBuilder = new PgsqlQueryBuilder();
        $expectedQueries = [
            'ALTER TABLE "old_table_name" RENAME TO "new_table_name";',
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->renameTable($table, 'new_table_name'));
    }
}
