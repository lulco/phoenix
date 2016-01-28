<?php

namespace Phoenix\Tests\Database\QueryBuilder;

use Phoenix\Database\Element\Column;
use Phoenix\Database\Element\Table;
use Phoenix\Database\QueryBuilder\MysqlQueryBuilder;
use PHPUnit_Framework_TestCase;

class MysqlQueryBuilderTest extends PHPUnit_Framework_TestCase
{
    public function testUnsupportedColumnType()
    {
        $table = new Table('unsupported');
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn('title', 'unsupported'));
        
        $queryCreator = new MysqlQueryBuilder();
        $this->setExpectedException('\Exception', 'Type "unsupported" is not allowed');
        $queryCreator->createTable($table);
    }

    public function testSimpleCreate()
    {
        $table = new Table('simple');
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn('title', 'string'));
        
        $queryCreator = new MysqlQueryBuilder();
        $expectedQueries = [
            'CREATE TABLE `simple` (`id` int(11) NOT NULL AUTO_INCREMENT,`title` varchar(255) NOT NULL,PRIMARY KEY (`id`)) DEFAULT CHARACTER SET=utf8 COLLATE=utf8_general_ci;'
        ];
        $this->assertEquals($expectedQueries, $queryCreator->createTable($table));
    }
    
    public function testMoreColumns()
    {
        $table = new Table('more_columns');
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn('title', 'string'));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn('alias', 'string', true));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn('total', 'integer', false, 0));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn('bodytext', 'text', false));
        
        $queryCreator = new MysqlQueryBuilder();
        $expectedQueries = [
            "CREATE TABLE `more_columns` (`id` int(11) NOT NULL AUTO_INCREMENT,`title` varchar(255) NOT NULL,`alias` varchar(255) DEFAULT NULL,`total` int(11) NOT NULL DEFAULT 0,`bodytext` text NOT NULL,PRIMARY KEY (`id`)) DEFAULT CHARACTER SET=utf8 COLLATE=utf8_general_ci;"
        ];
        $this->assertEquals($expectedQueries, $queryCreator->createTable($table));
    }
    
    public function testNoPrimaryKey()
    {
        $table = new Table('no_primary_key', false);
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn('title', 'string', true));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn('total', 'integer', false, 0));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn('is_deleted', 'boolean', false, false));
        
        $queryCreator = new MysqlQueryBuilder();
        $expectedQueries = [
            "CREATE TABLE `no_primary_key` (`title` varchar(255) DEFAULT NULL,`total` int(11) NOT NULL DEFAULT 0,`is_deleted` int(1) NOT NULL DEFAULT 0) DEFAULT CHARACTER SET=utf8 COLLATE=utf8_general_ci;"
        ];
        $this->assertEquals($expectedQueries, $queryCreator->createTable($table));
    }
    
    public function testOwnPrimaryKey()
    {
        $table = new Table('own_primary_key', new Column('identifier', 'string', false, null, 32));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn('title', 'string', false, ''));
        
        $queryCreator = new MysqlQueryBuilder();
        $expectedQueries = [
            "CREATE TABLE `own_primary_key` (`identifier` varchar(32) NOT NULL,`title` varchar(255) NOT NULL DEFAULT '',PRIMARY KEY (`identifier`)) DEFAULT CHARACTER SET=utf8 COLLATE=utf8_general_ci;"
        ];
        $this->assertEquals($expectedQueries, $queryCreator->createTable($table));
    }
    
    public function testMoreOwnPrimaryKeys()
    {
        $table = new Table('more_own_primary_keys', [new Column('identifier', 'string', false, null, 32), new Column('subidentifier', 'string', false, null, 32)]);
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn('title', 'string', false, ''));
        
        $queryCreator = new MysqlQueryBuilder();
        $expectedQueries = [
            "CREATE TABLE `more_own_primary_keys` (`identifier` varchar(32) NOT NULL,`subidentifier` varchar(32) NOT NULL,`title` varchar(255) NOT NULL DEFAULT '',PRIMARY KEY (`identifier`,`subidentifier`)) DEFAULT CHARACTER SET=utf8 COLLATE=utf8_general_ci;"
        ];
        $this->assertEquals($expectedQueries, $queryCreator->createTable($table));
    }
    
    public function testOneFieldAsPrimaryKey()
    {
        $table = new Table('one_field_as_pk', 'identifier');
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn('identifier', 'string', false, null, 32));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn('title', 'string', false, ''));
        
        $queryCreator = new MysqlQueryBuilder();
        $expectedQueries = [
            "CREATE TABLE `one_field_as_pk` (`identifier` varchar(32) NOT NULL,`title` varchar(255) NOT NULL DEFAULT '',PRIMARY KEY (`identifier`)) DEFAULT CHARACTER SET=utf8 COLLATE=utf8_general_ci;"
        ];
        $this->assertEquals($expectedQueries, $queryCreator->createTable($table));
    }
    
    public function testMoreFieldsAsPrimaryKeys()
    {
        $table = new Table('more_fields_as_pk', ['identifier', 'subidentifier']);
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn('identifier', 'string', false, null, 32));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn('subidentifier', 'string', false, null, 32));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn('title', 'string', false, ''));
        
        $queryCreator = new MysqlQueryBuilder();
        $expectedQueries = [
            "CREATE TABLE `more_fields_as_pk` (`identifier` varchar(32) NOT NULL,`subidentifier` varchar(32) NOT NULL,`title` varchar(255) NOT NULL DEFAULT '',PRIMARY KEY (`identifier`,`subidentifier`)) DEFAULT CHARACTER SET=utf8 COLLATE=utf8_general_ci;"
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
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn('identifier', 'string', false, null, 32));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn('title', 'string', false, ''));
        
        $queryCreator = new MysqlQueryBuilder();
        $this->setExpectedException('\Exception', 'Column "unknown" not found');
        $queryCreator->createTable($table);
    }
    
    public function testIndexes()
    {
        $table = new Table('table_with_indexes');
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn('title', 'string'));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn('alias', 'string'));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn('sorting', 'integer'));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn('bodytext', 'text'));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addIndex('sorting', '', 'btree'));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addIndex(['title', 'alias'], 'unique'));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addIndex('bodytext', 'fulltext', 'hash'));
        
        $queryCreator = new MysqlQueryBuilder();
        $expectedQueries = [
            "CREATE TABLE `table_with_indexes` (`id` int(11) NOT NULL AUTO_INCREMENT,`title` varchar(255) NOT NULL,`alias` varchar(255) NOT NULL,`sorting` int(11) NOT NULL,`bodytext` text NOT NULL,PRIMARY KEY (`id`),INDEX `sorting` (`sorting`) USING BTREE,UNIQUE INDEX `title_alias` (`title`,`alias`),FULLTEXT INDEX `bodytext` (`bodytext`) USING HASH) DEFAULT CHARACTER SET=utf8 COLLATE=utf8_general_ci;"
        ];
        $this->assertEquals($expectedQueries, $queryCreator->createTable($table));
    }
    
    public function testForeignKeys()
    {
        $table = new Table('table_with_foreign_keys');
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn('title', 'string'));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn('alias', 'string'));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn('foreign_table_id', 'integer'));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addForeignKey('foreign_table_id', 'second_table'));
        
        $queryCreator = new MysqlQueryBuilder();
        $expectedQueries = [
            "CREATE TABLE `table_with_foreign_keys` (`id` int(11) NOT NULL AUTO_INCREMENT,`title` varchar(255) NOT NULL,`alias` varchar(255) NOT NULL,`foreign_table_id` int(11) NOT NULL,PRIMARY KEY (`id`),CONSTRAINT `table_with_foreign_keys_foreign_table_id` FOREIGN KEY (`foreign_table_id`) REFERENCES `second_table` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT) DEFAULT CHARACTER SET=utf8 COLLATE=utf8_general_ci;"
        ];
        $this->assertEquals($expectedQueries, $queryCreator->createTable($table));
    }
    
    public function testIndexesAndForeignKeys()
    {
        $table = new Table('table_with_indexes_and_foreign_keys');
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn('title', 'string'));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn('alias', 'string'));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn('sorting', 'integer'));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn('bodytext', 'text'));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn('foreign_table_id', 'integer'));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addForeignKey('foreign_table_id', 'second_table', 'foreign_id', 'set null', 'set null'));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addIndex('sorting', '', 'btree'));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addIndex(['title', 'alias'], 'unique'));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addIndex('bodytext', 'fulltext', 'hash'));
        
        $queryCreator = new MysqlQueryBuilder();
        $expectedQueries = [
            "CREATE TABLE `table_with_indexes_and_foreign_keys` (`id` int(11) NOT NULL AUTO_INCREMENT,`title` varchar(255) NOT NULL,`alias` varchar(255) NOT NULL,`sorting` int(11) NOT NULL,`bodytext` text NOT NULL,`foreign_table_id` int(11) NOT NULL,PRIMARY KEY (`id`),INDEX `sorting` (`sorting`) USING BTREE,UNIQUE INDEX `title_alias` (`title`,`alias`),FULLTEXT INDEX `bodytext` (`bodytext`) USING HASH,CONSTRAINT `table_with_indexes_and_foreign_keys_foreign_table_id` FOREIGN KEY (`foreign_table_id`) REFERENCES `second_table` (`foreign_id`) ON DELETE SET NULL ON UPDATE SET NULL) DEFAULT CHARACTER SET=utf8 COLLATE=utf8_general_ci;"
        ];
        $this->assertEquals($expectedQueries, $queryCreator->createTable($table));
    }
    
    public function testDropTable()
    {
        $table = new Table('drop');
        $queryCreator = new MysqlQueryBuilder();
        $expectedQueries = [
            'DROP TABLE `drop`'
        ];
        $this->assertEquals($expectedQueries, $queryCreator->dropTable($table));
    }
    
    public function testAlterTable()
    {
        // add columns
        $table = new Table('add_columns');
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn('title', 'string'));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn('alias', 'string'));
        
        $queryCreator = new MysqlQueryBuilder();
        $expectedQueries = [
            'ALTER TABLE `add_columns` ADD COLUMN `title` varchar(255) NOT NULL,ADD COLUMN `alias` varchar(255) NOT NULL;'
        ];
        $this->assertEquals($expectedQueries, $queryCreator->alterTable($table));
        
        // add index
        $table = new Table('add_index');
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addIndex('alias', 'unique'));
        
        $queryCreator = new MysqlQueryBuilder();
        $expectedQueries = [
            'ALTER TABLE `add_index` ADD UNIQUE INDEX `alias` (`alias`);',
        ];
        $this->assertEquals($expectedQueries, $queryCreator->alterTable($table));
        
        // add column and index
        $table = new Table('add_column_and_index');
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn('alias', 'string'));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addIndex('alias', 'unique'));
        
        $queryCreator = new MysqlQueryBuilder();
        $expectedQueries = [
            'ALTER TABLE `add_column_and_index` ADD COLUMN `alias` varchar(255) NOT NULL;',
            'ALTER TABLE `add_column_and_index` ADD UNIQUE INDEX `alias` (`alias`);',
        ];
        $this->assertEquals($expectedQueries, $queryCreator->alterTable($table));
        
        // add foreign key, index, columns
        $table = new Table('add_columns_index_foreign_key');
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn('foreign_key_id', 'integer'));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn('sorting', 'integer'));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addIndex('sorting'));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addForeignKey('foreign_key_id', 'referenced_table'));
        
        $queryCreator = new MysqlQueryBuilder();
        $expectedQueries = [
            'ALTER TABLE `add_columns_index_foreign_key` ADD COLUMN `foreign_key_id` int(11) NOT NULL,ADD COLUMN `sorting` int(11) NOT NULL;',
            'ALTER TABLE `add_columns_index_foreign_key` ADD INDEX `sorting` (`sorting`);',
            'ALTER TABLE `add_columns_index_foreign_key` ADD CONSTRAINT `add_columns_index_foreign_key_foreign_key_id` FOREIGN KEY (`foreign_key_id`) REFERENCES `referenced_table` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;',
        ];
        $this->assertEquals($expectedQueries, $queryCreator->alterTable($table));
        
        // remove columns
        
        // remove index
        
        // remove foreign key
        
        // combination of add / remove column, add / remove index, add / remove foreign key
        $table = new Table('all_in_one');
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn('foreign_key_id', 'integer'));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn('sorting', 'integer'));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->dropColumn('title'));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addIndex('sorting'));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->dropIndex('alias'));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addForeignKey('foreign_key_id', 'referenced_table'));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->dropForeignKey('foreign_key_to_drop_id'));
        
        $queryCreator = new MysqlQueryBuilder();
        $expectedQueries = [
            'ALTER TABLE `all_in_one` DROP INDEX `alias`;',
            'ALTER TABLE `all_in_one` DROP FOREIGN KEY `all_in_one_foreign_key_to_drop_id`;',
            'ALTER TABLE `all_in_one` DROP COLUMN `title`;',
            'ALTER TABLE `all_in_one` ADD COLUMN `foreign_key_id` int(11) NOT NULL,ADD COLUMN `sorting` int(11) NOT NULL;',
            'ALTER TABLE `all_in_one` ADD INDEX `sorting` (`sorting`);',
            'ALTER TABLE `all_in_one` ADD CONSTRAINT `all_in_one_foreign_key_id` FOREIGN KEY (`foreign_key_id`) REFERENCES `referenced_table` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;',
        ];
        $this->assertEquals($expectedQueries, $queryCreator->alterTable($table));
        
        // mixed order of calls add / remove column, add / remove index, add / remove foreign key - output is the same
        $table = new Table('all_in_one_mixed');
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addIndex('sorting'));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->dropForeignKey('foreign_key_to_drop_id'));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn('foreign_key_id', 'integer'));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->dropColumn('title'));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn('sorting', 'integer'));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->dropIndex('alias'));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addForeignKey('foreign_key_id', 'referenced_table'));
                
        $queryCreator = new MysqlQueryBuilder();
        $expectedQueries = [
            'ALTER TABLE `all_in_one_mixed` DROP INDEX `alias`;',
            'ALTER TABLE `all_in_one_mixed` DROP FOREIGN KEY `all_in_one_mixed_foreign_key_to_drop_id`;',
            'ALTER TABLE `all_in_one_mixed` DROP COLUMN `title`;',
            'ALTER TABLE `all_in_one_mixed` ADD COLUMN `foreign_key_id` int(11) NOT NULL,ADD COLUMN `sorting` int(11) NOT NULL;',
            'ALTER TABLE `all_in_one_mixed` ADD INDEX `sorting` (`sorting`);',
            'ALTER TABLE `all_in_one_mixed` ADD CONSTRAINT `all_in_one_mixed_foreign_key_id` FOREIGN KEY (`foreign_key_id`) REFERENCES `referenced_table` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;',
        ];
        $this->assertEquals($expectedQueries, $queryCreator->alterTable($table));
    }
}
