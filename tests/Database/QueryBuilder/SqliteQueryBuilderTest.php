<?php

namespace Phoenix\Tests\Database\QueryBuilder;

use PDO;
use Phoenix\Database\Adapter\SqliteAdapter;
use Phoenix\Database\Element\Column;
use Phoenix\Database\Element\ForeignKey;
use Phoenix\Database\Element\Index;
use Phoenix\Database\Element\Table;
use Phoenix\Database\QueryBuilder\SqliteQueryBuilder;
use Phoenix\Tests\Mock\Database\FakePdo;
use PHPUnit_Framework_TestCase;

class SqliteQueryBuilderTest extends PHPUnit_Framework_TestCase
{
    public function testUnsupportedColumnType()
    {
        $table = new Table('unsupported');
        $table->addPrimary(true);
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('title', 'unsupported')));
        
        $pdo = new FakePdo();
        $adapter = new SqliteAdapter($pdo);
        
        $queryCreator = new SqliteQueryBuilder($adapter);
        $this->setExpectedException('\Exception', 'Type "unsupported" is not allowed');
        $queryCreator->createTable($table);
    }

    public function testSimpleCreate()
    {
        $table = new Table('simple');
        $table->addPrimary(true);
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('title', 'string')));
        
        $pdo = new FakePdo();
        $adapter = new SqliteAdapter($pdo);
        
        $queryCreator = new SqliteQueryBuilder($adapter);
        $expectedQueries = [
            'CREATE TABLE "simple" ("id" integer PRIMARY KEY AUTOINCREMENT NOT NULL,"title" varchar(255) NOT NULL);'
        ];
        $this->assertEquals($expectedQueries, $queryCreator->createTable($table));
    }
    
    public function testMoreColumns()
    {
        $table = new Table('more_columns');
        $table->addPrimary(true);
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('title', 'string')));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('alias', 'string', ['null' => true])));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('total', 'integer', ['default' => 0])));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('bodytext', 'text')));
        
        $pdo = new FakePdo();
        $adapter = new SqliteAdapter($pdo);
        
        $queryCreator = new SqliteQueryBuilder($adapter);
        $expectedQueries = [
            'CREATE TABLE "more_columns" ("id" integer PRIMARY KEY AUTOINCREMENT NOT NULL,"title" varchar(255) NOT NULL,"alias" varchar(255) DEFAULT NULL,"total" integer NOT NULL DEFAULT 0,"bodytext" text NOT NULL);'
        ];
        $this->assertEquals($expectedQueries, $queryCreator->createTable($table));
    }
    
    public function testNoPrimaryKey()
    {
        $table = new Table('no_primary_key');
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('title', 'string', ['null' => true])));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('total', 'integer', ['default' => 0])));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('is_deleted', 'boolean', ['default' => false])));
        
        $pdo = new FakePdo();
        $adapter = new SqliteAdapter($pdo);
        
        $queryCreator = new SqliteQueryBuilder($adapter);
        $expectedQueries = [
            'CREATE TABLE "no_primary_key" ("title" varchar(255) DEFAULT NULL,"total" integer NOT NULL DEFAULT 0,"is_deleted" boolean NOT NULL DEFAULT 0);'
        ];
        $this->assertEquals($expectedQueries, $queryCreator->createTable($table));
    }
    
    public function testOwnPrimaryKey()
    {
        $table = new Table('own_primary_key');
        $table->addPrimary(new Column('identifier', 'string', ['length' => 32]));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('title', 'string', ['default' => ''])));
        
        $pdo = new FakePdo();
        $adapter = new SqliteAdapter($pdo);
        
        $queryCreator = new SqliteQueryBuilder($adapter);
        $expectedQueries = [
            'CREATE TABLE "own_primary_key" ("identifier" varchar(32) NOT NULL,"title" varchar(255) NOT NULL,PRIMARY KEY ("identifier"));'
        ];
        $this->assertEquals($expectedQueries, $queryCreator->createTable($table));
    }
    
    public function testMoreOwnPrimaryKeys()
    {
        $table = new Table('more_own_primary_keys');
        $table->addPrimary([new Column('identifier', 'string', ['length' => 32]), new Column('subidentifier', 'string', ['length' => 32])]);
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('title', 'string', ['default' => ''])));
        
        $pdo = new FakePdo();
        $adapter = new SqliteAdapter($pdo);
        
        $queryCreator = new SqliteQueryBuilder($adapter);
        $expectedQueries = [
            'CREATE TABLE "more_own_primary_keys" ("identifier" varchar(32) NOT NULL,"subidentifier" varchar(32) NOT NULL,"title" varchar(255) NOT NULL,PRIMARY KEY ("identifier","subidentifier"));'
        ];
        $this->assertEquals($expectedQueries, $queryCreator->createTable($table));
    }
    
    public function testOneFieldAsPrimaryKey()
    {
        $table = new Table('one_field_as_pk');
        $table->addPrimary('identifier');
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('identifier', 'string', ['length' => 32])));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('title', 'string', ['default' => ''])));
        
        $pdo = new FakePdo();
        $adapter = new SqliteAdapter($pdo);
        
        $queryCreator = new SqliteQueryBuilder($adapter);
        $expectedQueries = [
            'CREATE TABLE "one_field_as_pk" ("identifier" varchar(32) NOT NULL,"title" varchar(255) NOT NULL,PRIMARY KEY ("identifier"));'
        ];
        $this->assertEquals($expectedQueries, $queryCreator->createTable($table));
    }
    
    public function testMoreFieldsAsPrimaryKeys()
    {
        $table = new Table('more_fields_as_pk');
        $table->addPrimary(['identifier', 'subidentifier']);
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('identifier', 'string', ['length' => 32])));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('subidentifier', 'string', ['default' => 'SUB', 'length' => 32])));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('title', 'string', ['default' => ''])));
        
        $pdo = new FakePdo();
        $adapter = new SqliteAdapter($pdo);
        
        $queryCreator = new SqliteQueryBuilder($adapter);
        $expectedQueries = [
            'CREATE TABLE "more_fields_as_pk" ("identifier" varchar(32) NOT NULL,"subidentifier" varchar(32) NOT NULL DEFAULT \'SUB\',"title" varchar(255) NOT NULL,PRIMARY KEY ("identifier","subidentifier"));'
        ];
        $this->assertEquals($expectedQueries, $queryCreator->createTable($table));
    }
    
    public function testIndexes()
    {
        $table = new Table('table_with_indexes');
        $table->addPrimary(true);
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('title', 'string')));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('alias', 'string')));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('sorting', 'integer')));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('bodytext', 'text')));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addIndex(new Index('sorting', 'table_with_indexes_sorting')));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addIndex(new Index(['title', 'alias'], 'table_with_indexes_title_alias', 'unique')));
        
        $pdo = new FakePdo();
        $adapter = new SqliteAdapter($pdo);
        
        $queryCreator = new SqliteQueryBuilder($adapter);
        $expectedQueries = [
            'CREATE TABLE "table_with_indexes" ("id" integer PRIMARY KEY AUTOINCREMENT NOT NULL,"title" varchar(255) NOT NULL,"alias" varchar(255) NOT NULL,"sorting" integer NOT NULL,"bodytext" text NOT NULL);',
            'CREATE INDEX "table_with_indexes_sorting" ON "table_with_indexes" ("sorting");',
            'CREATE UNIQUE INDEX "table_with_indexes_title_alias" ON "table_with_indexes" ("title","alias");',
        ];
        $this->assertEquals($expectedQueries, $queryCreator->createTable($table));
    }
    
    public function testForeignKeys()
    {
        $table = new Table('table_with_foreign_keys');
        $table->addPrimary(true);
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('title', 'string')));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('alias', 'string')));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('foreign_table_id', 'integer')));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addForeignKey(new ForeignKey('foreign_table_id', 'second_table')));
        
        $pdo = new FakePdo();
        $adapter = new SqliteAdapter($pdo);
        
        $queryCreator = new SqliteQueryBuilder($adapter);
        $expectedQueries = [
            'CREATE TABLE "table_with_foreign_keys" ("id" integer PRIMARY KEY AUTOINCREMENT NOT NULL,"title" varchar(255) NOT NULL,"alias" varchar(255) NOT NULL,"foreign_table_id" integer NOT NULL,CONSTRAINT "table_with_foreign_keys_foreign_table_id" FOREIGN KEY ("foreign_table_id") REFERENCES "second_table" ("id"));'
        ];
        $this->assertEquals($expectedQueries, $queryCreator->createTable($table));
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
        
        $pdo = new FakePdo();
        $adapter = new SqliteAdapter($pdo);
        
        $queryCreator = new SqliteQueryBuilder($adapter);
        $expectedQueries = [
            'CREATE TABLE "table_with_indexes_and_foreign_keys" ("id" integer PRIMARY KEY AUTOINCREMENT NOT NULL,"title" varchar(255) NOT NULL,"alias" varchar(255) NOT NULL,"sorting" integer NOT NULL,"bodytext" text NOT NULL,"foreign_table_id" integer NOT NULL,CONSTRAINT "table_with_indexes_and_foreign_keys_foreign_table_id" FOREIGN KEY ("foreign_table_id") REFERENCES "second_table" ("foreign_id") ON DELETE SET NULL ON UPDATE SET NULL);',
            'CREATE INDEX "table_with_indexes_and_foreign_keys_sorting" ON "table_with_indexes_and_foreign_keys" ("sorting");',
            'CREATE UNIQUE INDEX "table_with_indexes_and_foreign_keys_title_alias" ON "table_with_indexes_and_foreign_keys" ("title","alias");',
        ];
        $this->assertEquals($expectedQueries, $queryCreator->createTable($table));
    }
    
    public function testDropTable()
    {
        $table = new Table('drop');
        $pdo = new FakePdo();
        $adapter = new SqliteAdapter($pdo);
        
        $queryCreator = new SqliteQueryBuilder($adapter);
        $expectedQueries = [
            'DROP TABLE "drop"'
        ];
        $this->assertEquals($expectedQueries, $queryCreator->dropTable($table));
    }
    
    public function testAlterTable()
    {
        // add columns
        $table = new Table('add_columns');
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('title', 'string')));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('alias', 'string')));
        
        $pdo = new FakePdo();
        $adapter = new SqliteAdapter($pdo);
        
        $queryCreator = new SqliteQueryBuilder($adapter);
        $expectedQueries = [
            'ALTER TABLE "add_columns" ADD COLUMN "title" varchar(255) NOT NULL,ADD COLUMN "alias" varchar(255) NOT NULL;',
        ];
        $this->assertEquals($expectedQueries, $queryCreator->alterTable($table));
    }
    
    public function testChangeColumn()
    {
        $pdo = new PDO('sqlite::memory:');
        $adapter = new SqliteAdapter($pdo);

        $pdo->query('CREATE TABLE "with_columns_to_change" ("id" integer PRIMARY KEY AUTOINCREMENT NOT NULL,"old_name" integer NOT NULL,"no_name_change" integer NOT NULL);');
        
        $table = new Table('with_columns_to_change');
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->changeColumn('old_name', new Column('new_name', 'integer')));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->changeColumn('no_name_change', new Column('no_name_change', 'integer')));

        $timestamp = date('YmdHis');
        $queryBuilder = new SqliteQueryBuilder($adapter);
        $expectedQueries = [
            'ALTER TABLE "with_columns_to_change" RENAME TO "_with_columns_to_change_old_' . $timestamp . '";',
            'CREATE TABLE "with_columns_to_change" ("id" integer PRIMARY KEY AUTOINCREMENT NOT NULL,"new_name" integer NOT NULL,"no_name_change" integer NOT NULL);',
            'INSERT INTO "with_columns_to_change" ("id","new_name","no_name_change") SELECT "id","old_name","no_name_change" FROM "_with_columns_to_change_old_' . $timestamp . '"',
            'DROP TABLE "_with_columns_to_change_old_' . $timestamp . '"',
        ];
        
        $this->assertEquals($expectedQueries, $queryBuilder->alterTable($table));
    }
    
    public function testChangeColumnWithoutAdapter()
    {
        $table = new Table('with_columns_to_change');
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->changeColumn('old_name', new Column('new_name', 'integer')));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->changeColumn('no_name_change', new Column('no_name_change', 'integer')));

        $queryBuilder = new SqliteQueryBuilder();
        
        $this->setExpectedException('\Phoenix\Exception\PhoenixException', 'Missing adapter');
        $queryBuilder->alterTable($table);
    }
    
    public function testChangeAddedColumn()
    {
        $table = new Table('with_change_added_column');
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('old_name', 'integer')));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->changeColumn('old_name', new Column('new_name', 'string')));

        $queryBuilder = new SqliteQueryBuilder();
        $expectedQueries = [
            'ALTER TABLE "with_change_added_column" ADD COLUMN "new_name" varchar(255) NOT NULL;',
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->alterTable($table));
    }
    
    public function testRenameTable()
    {
        $table = new Table('old_table_name');
        $queryBuilder = new SqliteQueryBuilder();
        $expectedQueries = [
            'ALTER TABLE "old_table_name" RENAME TO "new_table_name";',
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->renameTable($table, 'new_table_name'));
    }
}
