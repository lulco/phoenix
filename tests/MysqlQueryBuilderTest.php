<?php

namespace Phoenix\Tests;

use Phoenix\QueryBuilder\Column;
use Phoenix\QueryBuilder\MysqlQueryBuilder;
use Phoenix\QueryBuilder\Table;
use PHPUnit_Framework_TestCase;

class MysqlQueryBuilderTest extends PHPUnit_Framework_TestCase
{
    public function testUnsupportedColumnType()
    {
        $table = new Table('unsupported');
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addColumn('title', 'unsupported'));
        
        $queryCreator = new MysqlQueryBuilder();
        $this->setExpectedException('\Exception', 'Type "unsupported" is not allowed');
        $queryCreator->createTable($table);
    }

    public function testSimpleCreate()
    {
        $table = new Table('simple');
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addColumn('title', 'string'));
        
        $queryCreator = new MysqlQueryBuilder();
        $expectedQuery = 'CREATE TABLE `simple` (`id` int(11) NOT NULL AUTO_INCREMENT,`title` varchar(255) NOT NULL,PRIMARY KEY (`id`)) DEFAULT CHARACTER SET=utf8 COLLATE=utf8_general_ci;';
        $this->assertEquals($expectedQuery, $queryCreator->createTable($table));
    }
    
    public function testMoreColumns()
    {
        $table = new Table('more_columns');
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addColumn('title', 'string'));
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addColumn('alias', 'string', true));
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addColumn('total', 'integer', false, 0));
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addColumn('bodytext', 'text', false));
        
        $queryCreator = new MysqlQueryBuilder();
        $expectedQuery = "CREATE TABLE `more_columns` (`id` int(11) NOT NULL AUTO_INCREMENT,`title` varchar(255) NOT NULL,`alias` varchar(255) DEFAULT NULL,`total` int(11) NOT NULL DEFAULT 0,`bodytext` text NOT NULL,PRIMARY KEY (`id`)) DEFAULT CHARACTER SET=utf8 COLLATE=utf8_general_ci;";
        $this->assertEquals($expectedQuery, $queryCreator->createTable($table));
    }
    
    public function testNoPrimaryKey()
    {
        $table = new Table('no_primary_key', false);
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addColumn('title', 'string', true));
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addColumn('total', 'integer', false, 0));
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addColumn('is_deleted', 'boolean', false, false));
        
        $queryCreator = new MysqlQueryBuilder();
        $expectedQuery = "CREATE TABLE `no_primary_key` (`title` varchar(255) DEFAULT NULL,`total` int(11) NOT NULL DEFAULT 0,`is_deleted` int(1) NOT NULL DEFAULT 0) DEFAULT CHARACTER SET=utf8 COLLATE=utf8_general_ci;";
        $this->assertEquals($expectedQuery, $queryCreator->createTable($table));
    }
    
    public function testOwnPrimaryKey()
    {
        $table = new Table('own_primary_key', new Column('identifier', 'string', false, null, true, 32));
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addColumn('title', 'string', false, ''));
        
        $queryCreator = new MysqlQueryBuilder();
        $expectedQuery = "CREATE TABLE `own_primary_key` (`identifier` varchar(32) NOT NULL,`title` varchar(255) NOT NULL DEFAULT '',PRIMARY KEY (`identifier`)) DEFAULT CHARACTER SET=utf8 COLLATE=utf8_general_ci;";
        $this->assertEquals($expectedQuery, $queryCreator->createTable($table));
    }
    
    public function testMoreOwnPrimaryKeys()
    {
        $table = new Table('more_own_primary_keys', [new Column('identifier', 'string', false, null, true, 32), new Column('subidentifier', 'string', false, null, true, 32)]);
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addColumn('title', 'string', false, ''));
        
        $queryCreator = new MysqlQueryBuilder();
        $expectedQuery = "CREATE TABLE `more_own_primary_keys` (`identifier` varchar(32) NOT NULL,`subidentifier` varchar(32) NOT NULL,`title` varchar(255) NOT NULL DEFAULT '',PRIMARY KEY (`identifier`,`subidentifier`)) DEFAULT CHARACTER SET=utf8 COLLATE=utf8_general_ci;";
        $this->assertEquals($expectedQuery, $queryCreator->createTable($table));
    }
    
    public function testOneFieldAsPrimaryKey()
    {
        $table = new Table('one_field_as_pk', 'identifier');
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addColumn('identifier', 'string', false, null, true, 32));
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addColumn('title', 'string', false, ''));
        
        $queryCreator = new MysqlQueryBuilder();
        $expectedQuery = "CREATE TABLE `one_field_as_pk` (`identifier` varchar(32) NOT NULL,`title` varchar(255) NOT NULL DEFAULT '',PRIMARY KEY (`identifier`)) DEFAULT CHARACTER SET=utf8 COLLATE=utf8_general_ci;";
        $this->assertEquals($expectedQuery, $queryCreator->createTable($table));
    }
    
    public function testMoreFieldsAsPrimaryKeys()
    {
        $table = new Table('more_fields_as_pk', ['identifier', 'subidentifier']);
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addColumn('identifier', 'string', false, null, true, 32));
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addColumn('subidentifier', 'string', false, null, true, 32));
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addColumn('title', 'string', false, ''));
        
        $queryCreator = new MysqlQueryBuilder();
        $expectedQuery = "CREATE TABLE `more_fields_as_pk` (`identifier` varchar(32) NOT NULL,`subidentifier` varchar(32) NOT NULL,`title` varchar(255) NOT NULL DEFAULT '',PRIMARY KEY (`identifier`,`subidentifier`)) DEFAULT CHARACTER SET=utf8 COLLATE=utf8_general_ci;";
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
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addColumn('identifier', 'string', false, null, true, 32));
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addColumn('title', 'string', false, ''));
        
        $queryCreator = new MysqlQueryBuilder();
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
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addIndex('bodytext', 'fulltext'));
        
        $queryCreator = new MysqlQueryBuilder();
        $expectedQuery = "CREATE TABLE `table_with_indexes` (`id` int(11) NOT NULL AUTO_INCREMENT,`title` varchar(255) NOT NULL,`alias` varchar(255) NOT NULL,`sorting` int(11) NOT NULL,`bodytext` text NOT NULL,PRIMARY KEY (`id`),INDEX `sorting` (`sorting`),UNIQUE INDEX `title_alias` (`title`,`alias`),FULLTEXT INDEX `bodytext` (`bodytext`)) DEFAULT CHARACTER SET=utf8 COLLATE=utf8_general_ci;";
        $this->assertEquals($expectedQuery, $queryCreator->createTable($table));
    }
    
    public function testDropTable()
    {
        $table = new Table('drop');
        $queryCreator = new MysqlQueryBuilder();
        $expectedQuery = 'DROP TABLE `drop`';
        $this->assertEquals($expectedQuery, $queryCreator->dropTable($table));
    }
}
