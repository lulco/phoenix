<?php

namespace Phoenix\Tests\Database\QueryBuilder;

use Phoenix\Database\Element\Column;
use Phoenix\Database\Element\Table;
use Phoenix\Database\QueryBuilder\SqliteQueryBuilder;
use PHPUnit_Framework_TestCase;

class SqliteQueryBuilderTest extends PHPUnit_Framework_TestCase
{
    public function testUnsupportedColumnType()
    {
        $table = new Table('unsupported');
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('title', 'unsupported')));
        
        $queryCreator = new SqliteQueryBuilder();
        $this->setExpectedException('\Exception', 'Type "unsupported" is not allowed');
        $queryCreator->createTable($table);
    }

    public function testSimpleCreate()
    {
        $table = new Table('simple');
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('title', 'string')));
        
        $queryCreator = new SqliteQueryBuilder();
        $expectedQueries = [
            'CREATE TABLE "simple" ("id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,"title" TEXT NOT NULL);'
        ];
        $this->assertEquals($expectedQueries, $queryCreator->createTable($table));
    }
    
    public function testMoreColumns()
    {
        $table = new Table('more_columns');
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('title', 'string')));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('alias', 'string', ['null' => true])));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('total', 'integer', ['default' => 0])));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('bodytext', 'text')));
        
        $queryCreator = new SqliteQueryBuilder();
        $expectedQueries = [
            'CREATE TABLE "more_columns" ("id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,"title" TEXT NOT NULL,"alias" TEXT DEFAULT NULL,"total" INTEGER NOT NULL DEFAULT 0,"bodytext" TEXT NOT NULL);'
        ];
        $this->assertEquals($expectedQueries, $queryCreator->createTable($table));
    }
    
    public function testNoPrimaryKey()
    {
        $table = new Table('no_primary_key', false);
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('title', 'string', ['null' => true])));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('total', 'integer', ['default' => 0])));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('is_deleted', 'boolean', ['default' => false])));
        
        $queryCreator = new SqliteQueryBuilder();
        $expectedQueries = [
            'CREATE TABLE "no_primary_key" ("title" TEXT DEFAULT NULL,"total" INTEGER NOT NULL DEFAULT 0,"is_deleted" INTEGER NOT NULL DEFAULT 0);'
        ];
        $this->assertEquals($expectedQueries, $queryCreator->createTable($table));
    }
    
    public function testOwnPrimaryKey()
    {
        $table = new Table('own_primary_key', new Column('identifier', 'string', ['length' => 32]));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('title', 'string', ['default' => ''])));
        
        $queryCreator = new SqliteQueryBuilder();
        $expectedQueries = [
            'CREATE TABLE "own_primary_key" ("identifier" TEXT NOT NULL,"title" TEXT NOT NULL,PRIMARY KEY ("identifier"));'
        ];
        $this->assertEquals($expectedQueries, $queryCreator->createTable($table));
    }
    
    public function testMoreOwnPrimaryKeys()
    {
        $table = new Table('more_own_primary_keys', [new Column('identifier', 'string', ['length' => 32]), new Column('subidentifier', 'string', ['length' => 32])]);
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('title', 'string', ['default' => ''])));
        
        $queryCreator = new SqliteQueryBuilder();
        $expectedQueries = [
            'CREATE TABLE "more_own_primary_keys" ("identifier" TEXT NOT NULL,"subidentifier" TEXT NOT NULL,"title" TEXT NOT NULL,PRIMARY KEY ("identifier","subidentifier"));'
        ];
        $this->assertEquals($expectedQueries, $queryCreator->createTable($table));
    }
    
    public function testOneFieldAsPrimaryKey()
    {
        $table = new Table('one_field_as_pk', 'identifier');
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('identifier', 'string', ['length' => 32])));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('title', 'string', ['default' => ''])));
        
        $queryCreator = new SqliteQueryBuilder();
        $expectedQueries = [
            'CREATE TABLE "one_field_as_pk" ("identifier" TEXT NOT NULL,"title" TEXT NOT NULL,PRIMARY KEY ("identifier"));'
        ];
        $this->assertEquals($expectedQueries, $queryCreator->createTable($table));
    }
    
    public function testMoreFieldsAsPrimaryKeys()
    {
        $table = new Table('more_fields_as_pk', ['identifier', 'subidentifier']);
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('identifier', 'string', ['length' => 32])));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('subidentifier', 'string', ['default' => 'SUB', 'length' => 32])));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('title', 'string', ['default' => ''])));
        
        $queryCreator = new SqliteQueryBuilder();
        $expectedQueries = [
            'CREATE TABLE "more_fields_as_pk" ("identifier" TEXT NOT NULL,"subidentifier" TEXT NOT NULL DEFAULT \'SUB\',"title" TEXT NOT NULL,PRIMARY KEY ("identifier","subidentifier"));'
        ];
        $this->assertEquals($expectedQueries, $queryCreator->createTable($table));
    }
    
    public function testUnsupportedTypeOfPrimaryKeys()
    {
        $this->setExpectedException('\InvalidArgumentException', 'Unsupported type of primary column');
        $table = new Table('more_fields_as_pk', ['identifier', false]);
    }
    
    public function testUnkownColumnAsPrimaryKey()
    {
        $table = new Table('unknown_primary_key', 'unknown');
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('identifier', 'string', ['length' => 32])));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('title', 'string', ['default' => ''])));
        
        $queryCreator = new SqliteQueryBuilder();
        $this->setExpectedException('\Exception', 'Column "unknown" not found');
        $queryCreator->createTable($table);
    }
    
    public function testIndexes()
    {
        $table = new Table('table_with_indexes');
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('title', 'string')));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('alias', 'string')));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('sorting', 'integer')));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('bodytext', 'text')));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addIndex('sorting'));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addIndex(['title', 'alias'], 'unique'));
        
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
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('title', 'string')));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('alias', 'string')));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('foreign_table_id', 'integer')));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addForeignKey('foreign_table_id', 'second_table'));
        
        $queryCreator = new SqliteQueryBuilder();
        $expectedQueries = [
            'CREATE TABLE "table_with_foreign_keys" ("id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,"title" TEXT NOT NULL,"alias" TEXT NOT NULL,"foreign_table_id" INTEGER NOT NULL,CONSTRAINT "table_with_foreign_keys_foreign_table_id" FOREIGN KEY ("foreign_table_id") REFERENCES "second_table" ("id") ON DELETE RESTRICT ON UPDATE RESTRICT);'
        ];
        $this->assertEquals($expectedQueries, $queryCreator->createTable($table));
    }
    
    public function testIndexesAndForeignKeys()
    {
        $table = new Table('table_with_indexes_and_foreign_keys');
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('title', 'string')));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('alias', 'string')));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('sorting', 'integer')));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('bodytext', 'text')));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('foreign_table_id', 'integer')));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addForeignKey('foreign_table_id', 'second_table', 'foreign_id', 'set null', 'set null'));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addIndex('sorting', '', 'btree'));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addIndex(['title', 'alias'], 'unique'));
        
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
        
        $queryCreator = new SqliteQueryBuilder();
        $expectedQueries = [];
        $this->assertEquals($expectedQueries, $queryCreator->alterTable($table));
    }
}

