<?php

namespace Phoenix\Tests;

use Phoenix\QueryBuilder\Column;
use Phoenix\QueryBuilder\SqliteQueryBuilder;
use Phoenix\QueryBuilder\Table;
use PHPUnit_Framework_TestCase;

class SqliteQueryBuilderTest extends PHPUnit_Framework_TestCase
{
    public function testUnsupportedColumnType()
    {
        $table = new Table('unsupported');
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addColumn('title', 'unsupported'));
        
        $queryCreator = new SqliteQueryBuilder();
        $this->setExpectedException('\Exception', 'Type "unsupported" is not allowed');
        $queryCreator->createTable($table);
    }

    public function testSimpleCreate()
    {
        $table = new Table('simple');
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addColumn('title', 'string'));
        
        $queryCreator = new SqliteQueryBuilder();
        $expectedQuery = 'CREATE TABLE "simple" ("id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,"title" TEXT NOT NULL);';
        $this->assertEquals($expectedQuery, $queryCreator->createTable($table));
    }
    
    public function testMoreColumns()
    {
        $table = new Table('more_columns');
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addColumn('title', 'string'));
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addColumn('alias', 'string', true));
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addColumn('total', 'integer', false, 0));
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addColumn('bodytext', 'text', false));
        
        $queryCreator = new SqliteQueryBuilder();
        $expectedQuery = 'CREATE TABLE "more_columns" ("id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,"title" TEXT NOT NULL,"alias" TEXT DEFAULT NULL,"total" INTEGER NOT NULL DEFAULT 0,"bodytext" TEXT NOT NULL);';
        $this->assertEquals($expectedQuery, $queryCreator->createTable($table));
    }
    
    public function testNoPrimaryKey()
    {
        $table = new Table('no_primary_key', false);
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addColumn('title', 'string', true));
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addColumn('total', 'integer', false, 0));
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addColumn('is_deleted', 'boolean', false, false));
        
        $queryCreator = new SqliteQueryBuilder();
        $expectedQuery = 'CREATE TABLE "no_primary_key" ("title" TEXT DEFAULT NULL,"total" INTEGER NOT NULL DEFAULT 0,"is_deleted" INTEGER NOT NULL DEFAULT 0);';
        $this->assertEquals($expectedQuery, $queryCreator->createTable($table));
    }
    
    public function testOwnPrimaryKey()
    {
        $table = new Table('own_primary_key', new Column('identifier', 'string', false, null, 32));
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addColumn('title', 'string', false, ''));
        
        $queryCreator = new SqliteQueryBuilder();
        $expectedQuery = 'CREATE TABLE "own_primary_key" ("identifier" TEXT NOT NULL,"title" TEXT NOT NULL,PRIMARY KEY ("identifier"));';
        $this->assertEquals($expectedQuery, $queryCreator->createTable($table));
    }
    
    public function testMoreOwnPrimaryKeys()
    {
        $table = new Table('more_own_primary_keys', [new Column('identifier', 'string', false, null, 32), new Column('subidentifier', 'string', false, null, 32)]);
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addColumn('title', 'string', false, ''));
        
        $queryCreator = new SqliteQueryBuilder();
        $expectedQuery = 'CREATE TABLE "more_own_primary_keys" ("identifier" TEXT NOT NULL,"subidentifier" TEXT NOT NULL,"title" TEXT NOT NULL,PRIMARY KEY ("identifier","subidentifier"));';
        $this->assertEquals($expectedQuery, $queryCreator->createTable($table));
    }
    
    public function testOneFieldAsPrimaryKey()
    {
        $table = new Table('one_field_as_pk', 'identifier');
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addColumn('identifier', 'string', false, null, 32));
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addColumn('title', 'string', false, ''));
        
        $queryCreator = new SqliteQueryBuilder();
        $expectedQuery = 'CREATE TABLE "one_field_as_pk" ("identifier" TEXT NOT NULL,"title" TEXT NOT NULL,PRIMARY KEY ("identifier"));';
        $this->assertEquals($expectedQuery, $queryCreator->createTable($table));
    }
    
    public function testMoreFieldsAsPrimaryKeys()
    {
        $table = new Table('more_fields_as_pk', ['identifier', 'subidentifier']);
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addColumn('identifier', 'string', false, null, 32));
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addColumn('subidentifier', 'string', false, 'SUB', 32));
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addColumn('title', 'string', false, ''));
        
        $queryCreator = new SqliteQueryBuilder();
        $expectedQuery = 'CREATE TABLE "more_fields_as_pk" ("identifier" TEXT NOT NULL,"subidentifier" TEXT NOT NULL DEFAULT \'SUB\',"title" TEXT NOT NULL,PRIMARY KEY ("identifier","subidentifier"));';
        $this->assertEquals($expectedQuery, $queryCreator->createTable($table));
    }
    
    public function testUnsupportedTypeOfPrimaryKeys()
    {
        $this->setExpectedException('\InvalidArgumentException', 'Unsupported type of primary column');
        $table = new Table('more_fields_as_pk', ['identifier', false]);
    }
    
    public function testUnkownColumnAsPrimaryKey()
    {
        $table = new Table('unknown_primary_key', 'unknown');
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addColumn('identifier', 'string', false, null, 32));
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addColumn('title', 'string', false, ''));
        
        $queryCreator = new SqliteQueryBuilder();
        $this->setExpectedException('\Exception', 'Column "unknown" not found');
        $queryCreator->createTable($table);
    }
    
    public function testIndexes()
    {
        $table = new Table('table_with_indexes');
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addColumn('title', 'string'));
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addColumn('alias', 'string'));
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addColumn('sorting', 'integer'));
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addColumn('bodytext', 'text'));
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addIndex('sorting'));
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addIndex(['title', 'alias'], 'unique'));
        
        $queryCreator = new SqliteQueryBuilder();
        $expectedQueries = [
            'CREATE TABLE "table_with_indexes" ("id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,"title" TEXT NOT NULL,"alias" TEXT NOT NULL,"sorting" INTEGER NOT NULL,"bodytext" TEXT NOT NULL);',
            'CREATE INDEX "table_with_indexes_sorting" ON "table_with_indexes" ("sorting");',
            'CREATE UNIQUE INDEX "table_with_indexes_title_alias" ON "table_with_indexes" ("title","alias");',
        ];
        $this->assertEquals($expectedQueries, $queryCreator->createTable($table));
    }
    
    public function testForeignKeys()
    {
        $table = new Table('table_with_foreign_keys');
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addColumn('title', 'string'));
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addColumn('alias', 'string'));
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addColumn('foreign_table_id', 'integer'));
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addForeignKey('foreign_table_id', 'second_table'));
        
        $queryCreator = new SqliteQueryBuilder();
        $expectedQuery = 'CREATE TABLE "table_with_foreign_keys" ("id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,"title" TEXT NOT NULL,"alias" TEXT NOT NULL,"foreign_table_id" INTEGER NOT NULL,CONSTRAINT "table_with_foreign_keys_foreign_table_id" FOREIGN KEY ("foreign_table_id") REFERENCES "second_table" ("id") ON DELETE RESTRICT ON UPDATE RESTRICT);';
        $this->assertEquals($expectedQuery, $queryCreator->createTable($table));
    }
    
    public function testIndexesAndForeignKeys()
    {
        $table = new Table('table_with_indexes_and_foreign_keys');
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addColumn('title', 'string'));
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addColumn('alias', 'string'));
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addColumn('sorting', 'integer'));
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addColumn('bodytext', 'text'));
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addColumn('foreign_table_id', 'integer'));
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addForeignKey('foreign_table_id', 'second_table', 'foreign_id', 'set null', 'set null'));
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addIndex('sorting', '', 'btree'));
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addIndex(['title', 'alias'], 'unique'));
        
        $queryCreator = new SqliteQueryBuilder();
        $expectedQueries = [
            'CREATE TABLE "table_with_indexes_and_foreign_keys" ("id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,"title" TEXT NOT NULL,"alias" TEXT NOT NULL,"sorting" INTEGER NOT NULL,"bodytext" TEXT NOT NULL,"foreign_table_id" INTEGER NOT NULL,CONSTRAINT "table_with_indexes_and_foreign_keys_foreign_table_id" FOREIGN KEY ("foreign_table_id") REFERENCES "second_table" ("foreign_id") ON DELETE SET NULL ON UPDATE SET NULL);',
            'CREATE INDEX "table_with_indexes_and_foreign_keys_sorting" ON "table_with_indexes_and_foreign_keys" ("sorting");',
            'CREATE UNIQUE INDEX "table_with_indexes_and_foreign_keys_title_alias" ON "table_with_indexes_and_foreign_keys" ("title","alias");',
        ];
        $this->assertEquals($expectedQueries, $queryCreator->createTable($table));
    }
    
    public function testDropTable()
    {
        $table = new Table('drop');
        $queryCreator = new SqliteQueryBuilder();
        $expectedQuery = 'DROP TABLE "drop"';
        $this->assertEquals($expectedQuery, $queryCreator->dropTable($table));
    }
}
