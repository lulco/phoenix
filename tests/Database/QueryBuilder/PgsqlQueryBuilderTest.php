<?php

namespace Phoenix\Tests\Database\QueryBuilder;

use InvalidArgumentException;
use Phoenix\Database\Adapter\PgsqlAdapter;
use Phoenix\Database\Element\Column;
use Phoenix\Database\Element\MigrationTable;
use Phoenix\Database\QueryBuilder\PgsqlQueryBuilder;
use Phoenix\Tests\Helpers\Adapter\PgsqlCleanupAdapter;
use Phoenix\Tests\Helpers\Pdo\PgsqlPdo;
use PHPUnit\Framework\TestCase;

class PgsqlQueryBuilderTest extends TestCase
{
    private $adapter;

    protected function setUp(): void
    {
        $pdo = new PgsqlPdo();
        $adapter = new PgsqlCleanupAdapter($pdo);
        $adapter->cleanupDatabase();

        $pdo = new PgsqlPdo(getenv('PHOENIX_PGSQL_DATABASE'));
        $this->adapter = new PgsqlAdapter($pdo);
    }

    public function testSimpleCreate()
    {
        $table = new MigrationTable('simple');
        $table->addPrimary(true);
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('title', 'string'));

        $queryBuilder = new PgsqlQueryBuilder($this->adapter);
        $expectedQueries = [
            'CREATE TABLE "simple" ("id" serial NOT NULL,"title" varchar(255) NOT NULL,CONSTRAINT "simple_pkey" PRIMARY KEY ("id"));'
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

        $queryBuilder = new PgsqlQueryBuilder($this->adapter);
        $expectedQueries = [
            'CREATE TABLE "more_columns" ("id" serial NOT NULL,"title" varchar(255) NOT NULL,"alias" varchar(255) DEFAULT NULL,"total" int4 DEFAULT 0 NOT NULL,"bodytext" text NOT NULL,CONSTRAINT "more_columns_pkey" PRIMARY KEY ("id"));',
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->createTable($table));
    }

    public function testAllTypes()
    {
        $table = new MigrationTable('all_types');
        $table->addPrimary('id');
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('id', 'biginteger', ['autoincrement' => true]));
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('col_uuid', 'uuid'));
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('col_bit', 'bit'));
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
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('col_time', 'time', ['null' => true]));
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('col_timestamp', 'timestamp', ['null' => true, 'default' => 'CURRENT_TIMESTAMP']));
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('col_timestamp_tz', 'timestamptz', ['null' => true]));
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('col_year', 'year'));

        $queryBuilder = new PgsqlQueryBuilder($this->adapter);
        $expectedQueries = [
            'CREATE TYPE "all_types__col_enum" AS ENUM (\'xxx\',\'yyy\',\'zzz\');',
            'CREATE TYPE "all_types__col_set" AS ENUM (\'xxx\',\'yyy\',\'zzz\');',
            'CREATE TABLE "all_types" ("id" bigserial NOT NULL,"col_uuid" uuid NOT NULL,"col_bit" bit(32) NOT NULL,"col_tinyint" int2 NOT NULL,"col_smallint" int2 NOT NULL,"col_mediumint" int4 NOT NULL,"col_int" int4 NOT NULL,"col_bigint" int8 NOT NULL,"col_string" varchar(255) NOT NULL,"col_char" char(255) NOT NULL,"col_binary" bytea NOT NULL,"col_varbinary" bytea NOT NULL,"col_tinytext" text NOT NULL,"col_mediumtext" text NOT NULL,"col_text" text NOT NULL,"col_longtext" text NOT NULL,"col_tinyblob" bytea NOT NULL,"col_mediumblob" bytea NOT NULL,"col_blob" bytea NOT NULL,"col_longblob" bytea NOT NULL,"col_json" json NOT NULL,"col_numeric" numeric(10,3) NOT NULL,"col_decimal" numeric(10,3) NOT NULL,"col_float" float4 NOT NULL,"col_double" float8 NOT NULL,"col_boolean" bool DEFAULT false NOT NULL,"col_datetime" timestamp(6) NOT NULL,"col_date" date NOT NULL,"col_enum" all_types__col_enum NOT NULL,"col_set" all_types__col_set[] NOT NULL,"col_point" point DEFAULT NULL,"col_line" line DEFAULT NULL,"col_polygon" polygon DEFAULT NULL,"col_time" time DEFAULT NULL,"col_timestamp" timestamp(6) DEFAULT CURRENT_TIMESTAMP,"col_timestamp_tz" timestamptz DEFAULT NULL,"col_year" numeric(4) NOT NULL,CONSTRAINT "all_types_pkey" PRIMARY KEY ("id"));'
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->createTable($table));
    }

    public function testNoPrimaryKey()
    {
        $table = new MigrationTable('no_primary_key');
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('title', 'string', ['null' => true]));
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('total', 'integer', ['default' => 0]));
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('is_deleted', 'boolean', ['default' => false]));

        $queryBuilder = new PgsqlQueryBuilder($this->adapter);
        $expectedQueries = [
            'CREATE TABLE "no_primary_key" ("title" varchar(255) DEFAULT NULL,"total" int4 DEFAULT 0 NOT NULL,"is_deleted" bool DEFAULT false NOT NULL);'
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->createTable($table));
    }

    public function testOwnPrimaryKey()
    {
        $table = new MigrationTable('own_primary_key');
        $table->addPrimary(new Column('identifier', 'string', ['length' => 32]));
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('title', 'string', ['default' => '']));

        $queryBuilder = new PgsqlQueryBuilder($this->adapter);
        $expectedQueries = [
            'CREATE TABLE "own_primary_key" ("identifier" varchar(32) NOT NULL,"title" varchar(255) DEFAULT \'\' NOT NULL,CONSTRAINT "own_primary_key_pkey" PRIMARY KEY ("identifier"));'
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->createTable($table));
    }

    public function testMoreOwnPrimaryKeys()
    {
        $table = new MigrationTable('more_own_primary_keys');
        $table->addPrimary([new Column('identifier', 'string', ['length' => 32]), new Column('subidentifier', 'string', ['length' => 32])]);
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('title', 'string', ['default' => '']));

        $queryBuilder = new PgsqlQueryBuilder($this->adapter);
        $expectedQueries = [
            'CREATE TABLE "more_own_primary_keys" ("identifier" varchar(32) NOT NULL,"subidentifier" varchar(32) NOT NULL,"title" varchar(255) DEFAULT \'\' NOT NULL,CONSTRAINT "more_own_primary_keys_pkey" PRIMARY KEY ("identifier","subidentifier"));'
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->createTable($table));
    }

    public function testOneFieldAsPrimaryKey()
    {
        $table = new MigrationTable('one_field_as_pk');
        $table->addPrimary('identifier');
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('identifier', 'string', ['length' => 32]));
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('title', 'string', ['default' => '']));

        $queryBuilder = new PgsqlQueryBuilder($this->adapter);
        $expectedQueries = [
            'CREATE TABLE "one_field_as_pk" ("identifier" varchar(32) NOT NULL,"title" varchar(255) DEFAULT \'\' NOT NULL,CONSTRAINT "one_field_as_pk_pkey" PRIMARY KEY ("identifier"));',
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->createTable($table));
    }

    public function testMoreFieldsAsPrimaryKeys()
    {
        $table = new MigrationTable('more_fields_as_pk');
        $table->addPrimary(['identifier', 'subidentifier']);
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('identifier', 'string', ['length' => 32]));
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('subidentifier', 'string', ['length' => 32]));
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('title', 'string', ['default' => '']));

        $queryBuilder = new PgsqlQueryBuilder($this->adapter);
        $expectedQueries = [
            'CREATE TABLE "more_fields_as_pk" ("identifier" varchar(32) NOT NULL,"subidentifier" varchar(32) NOT NULL,"title" varchar(255) DEFAULT \'\' NOT NULL,CONSTRAINT "more_fields_as_pk_pkey" PRIMARY KEY ("identifier","subidentifier"));'
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->createTable($table));
    }

    public function testCreateTableWithCommentOnColumn()
    {
        $table = new MigrationTable('table_with_column_comment');
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('column_without_comment', 'string'));
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('column_with_comment', 'string', ['comment' => 'My comment']));
        $table->create();

        $queryBuilder = new PgsqlQueryBuilder($this->adapter);
        $expectedQueries = [
            'CREATE TABLE "table_with_column_comment" ("id" serial NOT NULL,"column_without_comment" varchar(255) NOT NULL,"column_with_comment" varchar(255) NOT NULL,CONSTRAINT "table_with_column_comment_pkey" PRIMARY KEY ("id"));',
            "COMMENT ON COLUMN table_with_column_comment.column_with_comment IS 'My comment';",
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->createTable($table));
    }

    public function testAddCommentToExistingColumn()
    {
        $table = new MigrationTable('table_with_column_comment');
        $this->assertInstanceOf(MigrationTable::class, $table->changeColumn('column_to_comment', 'column_to_comment', 'string', ['comment' => 'My comment']));
        $table->save();

        $queryBuilder = new PgsqlQueryBuilder($this->adapter);
        $expectedQueries = [
            'ALTER TABLE "table_with_column_comment" ALTER COLUMN "column_to_comment" TYPE varchar(255) USING column_to_comment::varchar;',
            'ALTER TABLE "table_with_column_comment" ALTER COLUMN "column_to_comment" SET NOT NULL;',
            'ALTER TABLE "table_with_column_comment" ALTER COLUMN "column_to_comment" DROP DEFAULT;',
            "COMMENT ON COLUMN table_with_column_comment.column_to_comment IS 'My comment';",
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->alterTable($table));
    }

    public function testIndexes()
    {
        $table = new MigrationTable('table_with_indexes');
        $table->addPrimary(true);
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('title', 'string'));
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('alias', 'string'));
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('sorting', 'integer'));
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('bodytext', 'text'));
        $this->assertInstanceOf(MigrationTable::class, $table->addIndex('sorting', '', 'btree', 'table_with_indexes_sorting'));
        $this->assertInstanceOf(MigrationTable::class, $table->addIndex(['title', 'alias'], 'unique', '', 'table_with_indexes_title_alias'));
        $this->assertInstanceOf(MigrationTable::class, $table->addIndex('bodytext', 'fulltext', 'hash', 'table_with_indexes_bodytext'));

        $queryBuilder = new PgsqlQueryBuilder($this->adapter);
        $expectedQueries = [
            'CREATE TABLE "table_with_indexes" ("id" serial NOT NULL,"title" varchar(255) NOT NULL,"alias" varchar(255) NOT NULL,"sorting" int4 NOT NULL,"bodytext" text NOT NULL,CONSTRAINT "table_with_indexes_pkey" PRIMARY KEY ("id"));',
            'CREATE INDEX "table_with_indexes_sorting" ON "table_with_indexes" USING BTREE ("sorting");',
            'CREATE UNIQUE INDEX "table_with_indexes_title_alias" ON "table_with_indexes" ("title","alias");',
            'CREATE FULLTEXT INDEX "table_with_indexes_bodytext" ON "table_with_indexes" USING HASH ("bodytext");',
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

        $queryBuilder = new PgsqlQueryBuilder($this->adapter);
        $expectedQueries = [
            'CREATE TABLE "table_with_foreign_keys" ("id" serial NOT NULL,"title" varchar(255) NOT NULL,"alias" varchar(255) NOT NULL,"foreign_table_id" int4 NOT NULL,CONSTRAINT "table_with_foreign_keys_pkey" PRIMARY KEY ("id"),CONSTRAINT "table_with_foreign_keys_foreign_table_id" FOREIGN KEY ("foreign_table_id") REFERENCES "second_table" ("id"));'
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
        $this->assertInstanceOf(MigrationTable::class, $table->addIndex('bodytext', 'fulltext', 'hash', 'table_with_indexes_and_foreign_keys_bodytext'));

        $queryBuilder = new PgsqlQueryBuilder($this->adapter);
        $expectedQueries = [
            'CREATE TABLE "table_with_indexes_and_foreign_keys" ("id" serial NOT NULL,"title" varchar(255) NOT NULL,"alias" varchar(255) NOT NULL,"sorting" int4 NOT NULL,"bodytext" text NOT NULL,"foreign_table_id" int4 NOT NULL,CONSTRAINT "table_with_indexes_and_foreign_keys_pkey" PRIMARY KEY ("id"),CONSTRAINT "table_with_indexes_and_foreign_keys_foreign_table_id" FOREIGN KEY ("foreign_table_id") REFERENCES "second_table" ("foreign_id") ON DELETE SET NULL ON UPDATE SET NULL);',
            'CREATE INDEX "table_with_indexes_and_foreign_keys_sorting" ON "table_with_indexes_and_foreign_keys" USING BTREE ("sorting");',
            'CREATE UNIQUE INDEX "table_with_indexes_and_foreign_keys_title_alias" ON "table_with_indexes_and_foreign_keys" ("title","alias");',
            'CREATE FULLTEXT INDEX "table_with_indexes_and_foreign_keys_bodytext" ON "table_with_indexes_and_foreign_keys" USING HASH ("bodytext");',
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->createTable($table));
    }

    public function testDropMigrationTable()
    {
        $table = new MigrationTable('drop');
        $queryBuilder = new PgsqlQueryBuilder($this->adapter);
        $expectedQueries = [
            'DROP TABLE "drop";',
            'DELETE FROM "pg_type" WHERE "typname" LIKE \'drop__%\';',
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->dropTable($table));
    }

    public function testAlterMigrationTable()
    {
        // add columns
        $table = new MigrationTable('add_columns');
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('title', 'string'));
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('alias', 'string'));

        $queryBuilder = new PgsqlQueryBuilder($this->adapter);
        $expectedQueries = [
            'ALTER TABLE "add_columns" ADD COLUMN "title" varchar(255) NOT NULL,ADD COLUMN "alias" varchar(255) NOT NULL;'
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->alterTable($table));

        // add and remove primary key
        $table = new MigrationTable('change_primary_key');
        $this->assertInstanceOf(MigrationTable::class, $table->dropPrimaryKey());
        $this->assertInstanceOf(MigrationTable::class, $table->addPrimary('new_primary'));

        $queryBuilder = new PgsqlQueryBuilder($this->adapter);
        $expectedQueries = [
            'ALTER TABLE "change_primary_key" DROP CONSTRAINT "change_primary_key_pkey";',
            'ALTER TABLE "change_primary_key" ADD CONSTRAINT "change_primary_key_pkey" PRIMARY KEY ("new_primary");',
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->alterTable($table));

        // add index
        $table = new MigrationTable('add_index');
        $this->assertInstanceOf(MigrationTable::class, $table->addIndex('alias', 'unique', '', 'add_index_alias'));

        $queryBuilder = new PgsqlQueryBuilder($this->adapter);
        $expectedQueries = [
            'CREATE UNIQUE INDEX "add_index_alias" ON "add_index" ("alias");',
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->alterTable($table));

        // add column and index
        $table = new MigrationTable('add_column_and_index');
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('alias', 'string'));
        $this->assertInstanceOf(MigrationTable::class, $table->addIndex('alias', 'unique', '', 'add_column_and_index_alias'));

        $queryBuilder = new PgsqlQueryBuilder($this->adapter);
        $expectedQueries = [
            'ALTER TABLE "add_column_and_index" ADD COLUMN "alias" varchar(255) NOT NULL;',
            'CREATE UNIQUE INDEX "add_column_and_index_alias" ON "add_column_and_index" ("alias");',
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->alterTable($table));

        // add foreign key, index, columns
        $table = new MigrationTable('add_columns_index_foreign_key');
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('foreign_key_id', 'integer'));
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('sorting', 'integer'));
        $this->assertInstanceOf(MigrationTable::class, $table->addIndex('sorting', '', '', 'add_columns_index_foreign_key_sorting'));
        $this->assertInstanceOf(MigrationTable::class, $table->addForeignKey('foreign_key_id', 'referenced_table'));

        $queryBuilder = new PgsqlQueryBuilder($this->adapter);
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
        $table = new MigrationTable('all_in_one');
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('foreign_key_id', 'integer'));
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('sorting', 'integer'));
        $this->assertInstanceOf(MigrationTable::class, $table->dropColumn('title'));
        $this->assertInstanceOf(MigrationTable::class, $table->addIndex('sorting', '', '', 'all_in_one_sorting'));
        $this->assertInstanceOf(MigrationTable::class, $table->dropIndex('alias'));
        $this->assertInstanceOf(MigrationTable::class, $table->addForeignKey('foreign_key_id', 'referenced_table'));
        $this->assertInstanceOf(MigrationTable::class, $table->dropForeignKey('foreign_key_to_drop_id'));

        $queryBuilder = new PgsqlQueryBuilder($this->adapter);
        $expectedQueries = [
            'DROP INDEX "idx_all_in_one_alias";',
            'ALTER TABLE "all_in_one" DROP CONSTRAINT "all_in_one_foreign_key_to_drop_id";',
            'ALTER TABLE "all_in_one" DROP COLUMN "title";',
            'ALTER TABLE "all_in_one" ADD COLUMN "foreign_key_id" int4 NOT NULL,ADD COLUMN "sorting" int4 NOT NULL;',
            'CREATE INDEX "all_in_one_sorting" ON "all_in_one" ("sorting");',
            'ALTER TABLE "all_in_one" ADD CONSTRAINT "all_in_one_foreign_key_id" FOREIGN KEY ("foreign_key_id") REFERENCES "referenced_table" ("id");',
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->alterTable($table));

        // mixed order of calls add / remove column, add / remove index, add / remove foreign key - output is the same
        $table = new MigrationTable('all_in_one_mixed');
        $this->assertInstanceOf(MigrationTable::class, $table->addIndex('sorting', '', '', 'all_in_one_mixed_sorting'));
        $this->assertInstanceOf(MigrationTable::class, $table->dropForeignKey('foreign_key_to_drop_id'));
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('foreign_key_id', 'integer'));
        $this->assertInstanceOf(MigrationTable::class, $table->dropColumn('title'));
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('sorting', 'integer'));
        $this->assertInstanceOf(MigrationTable::class, $table->dropIndexByName('all_in_one_mixed_alias'));
        $this->assertInstanceOf(MigrationTable::class, $table->addForeignKey('foreign_key_id', 'referenced_table'));

        $queryBuilder = new PgsqlQueryBuilder($this->adapter);
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
        $table = new MigrationTable('with_columns_to_change');
        $this->assertInstanceOf(MigrationTable::class, $table->changeColumn('old_name', 'new_name', 'integer'));
        $this->assertInstanceOf(MigrationTable::class, $table->changeColumn('no_name_change', 'no_name_change', 'integer'));
        $this->assertInstanceOf(MigrationTable::class, $table->changeColumn('default_null_change', 'default_null_change', 'string', ['null' => true]));
        $this->assertInstanceOf(MigrationTable::class, $table->changeColumn('default_null_with_value_change', 'default_null_with_value_change', 'string', ['null' => true, 'default' => 'default_value']));

        $queryBuilder = new PgsqlQueryBuilder($this->adapter);
        $expectedQueries = [
            'ALTER TABLE "with_columns_to_change" RENAME COLUMN "old_name" TO "new_name";',
            'ALTER TABLE "with_columns_to_change" ALTER COLUMN "new_name" TYPE int4 USING new_name::integer;',
            'ALTER TABLE "with_columns_to_change" ALTER COLUMN "new_name" SET NOT NULL;',
            'ALTER TABLE "with_columns_to_change" ALTER COLUMN "new_name" DROP DEFAULT;',
            'ALTER TABLE "with_columns_to_change" ALTER COLUMN "no_name_change" TYPE int4 USING no_name_change::integer;',
            'ALTER TABLE "with_columns_to_change" ALTER COLUMN "no_name_change" SET NOT NULL;',
            'ALTER TABLE "with_columns_to_change" ALTER COLUMN "no_name_change" DROP DEFAULT;',
            'ALTER TABLE "with_columns_to_change" ALTER COLUMN "default_null_change" TYPE varchar(255) USING default_null_change::varchar;',
            'ALTER TABLE "with_columns_to_change" ALTER COLUMN "default_null_change" DROP NOT NULL;',
            'ALTER TABLE "with_columns_to_change" ALTER COLUMN "default_null_change" SET DEFAULT NULL;',
            'ALTER TABLE "with_columns_to_change" ALTER COLUMN "default_null_with_value_change" TYPE varchar(255) USING default_null_with_value_change::varchar;',
            'ALTER TABLE "with_columns_to_change" ALTER COLUMN "default_null_with_value_change" DROP NOT NULL;',
            'ALTER TABLE "with_columns_to_change" ALTER COLUMN "default_null_with_value_change" SET DEFAULT \'default_value\';',
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->alterTable($table));
    }

    public function testRenameMigrationTable()
    {
        $table = new MigrationTable('old_table_name');
        $table->rename('new_table_name');
        $queryBuilder = new PgsqlQueryBuilder($this->adapter);
        $expectedQueries = [
            'ALTER TABLE "old_table_name" RENAME TO "new_table_name";',
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->renameTable($table));
    }

    public function testCreateTableWithComment()
    {
        $table = new MigrationTable('table_with_comment');
        $table->setComment('test table with comment');
        $table->addColumn('title', 'string');
        $table->create();
        $queryBuilder = new PgsqlQueryBuilder($this->adapter);
        $expectedQueries = [
            'CREATE TABLE "table_with_comment" ("id" serial NOT NULL,"title" varchar(255) NOT NULL,CONSTRAINT "table_with_comment_pkey" PRIMARY KEY ("id"));',
            'COMMENT ON TABLE table_with_comment IS \'test table with comment\';',
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->createTable($table));
    }

    public function testAddCommentToExistingTable()
    {
        $table = new MigrationTable('table_with_comment');
        $table->setComment('test table with comment');
        $table->save();
        $queryBuilder = new PgsqlQueryBuilder($this->adapter);
        $expectedQueries = [
            'COMMENT ON TABLE table_with_comment IS \'test table with comment\';',
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->alterTable($table));
    }

    public function testAddPrimaryColumnsAndColumnNamesException()
    {
        $queryBuilder = new PgsqlQueryBuilder($this->adapter);
        $table = new MigrationTable('add_primary_columns', false);
        $table->addColumn('title', 'string')
            ->addColumn('bodytext', 'text')
            ->create();

        foreach ($queryBuilder->createTable($table) as $query) {
            $this->adapter->query($query);
        }

        $queryBuilder = new PgsqlQueryBuilder($this->adapter);
        $table = new MigrationTable('add_primary_columns');
        $table->addPrimary(new Column('id', 'integer'));
        $table->addPrimaryColumns([new Column('identifier', 'string')]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot combine addPrimary() and addPrimaryColumns() in one migration');
        $queryBuilder->alterTable($table);
    }
}
