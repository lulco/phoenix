<?php

namespace Phoenix\Tests\Database\QueryBuilder;

use Phoenix\Database\Element\Column;
use Phoenix\Database\Element\ForeignKey;
use Phoenix\Database\Element\Index;
use Phoenix\Database\Element\Table;
use Phoenix\Database\QueryBuilder\MysqlQueryBuilder;
use PHPUnit_Framework_TestCase;

class MysqlQueryBuilderTest extends PHPUnit_Framework_TestCase
{
    public function testUnsupportedColumnType()
    {
        $table = new Table('unsupported');
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('title', 'unsupported')));
        
        $queryBuilder = new MysqlQueryBuilder();
        $this->setExpectedException('\Exception', 'Type "unsupported" is not allowed');
        $queryBuilder->createTable($table);
    }

    public function testSimpleCreate()
    {
        $table = new Table('simple');
        $table->addPrimary(true);
        $table->setCharset('utf8');
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('title', 'string')));
        
        $queryBuilder = new MysqlQueryBuilder();
        $expectedQueries = [
            'CREATE TABLE `simple` (`id` int(11) NOT NULL AUTO_INCREMENT,`title` varchar(255) NOT NULL,PRIMARY KEY (`id`)) DEFAULT CHARACTER SET=utf8;'
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->createTable($table));
    }
    
    public function testMoreColumns()
    {
        $table = new Table('more_columns');
        $table->addPrimary(true);
        $table->setCharset('utf8');
        $table->setCollation('utf8_general_ci');
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('title', 'string', ['charset' => 'utf16'])));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('alias', 'string', ['null' => true])));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('total', 'integer', ['default' => 0])));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('bodytext', 'text', ['collation' => 'utf8_slovak_ci'])));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('price', 'decimal', ['length' => 8, 'decimals' => 2])));
        
        $queryBuilder = new MysqlQueryBuilder();
        $expectedQueries = [
            "CREATE TABLE `more_columns` (`id` int(11) NOT NULL AUTO_INCREMENT,`title` varchar(255) CHARACTER SET utf16 NOT NULL,`alias` varchar(255) DEFAULT NULL,`total` int(11) NOT NULL DEFAULT 0,`bodytext` text COLLATE utf8_slovak_ci NOT NULL,`price` decimal(8,2) NOT NULL,PRIMARY KEY (`id`)) DEFAULT CHARACTER SET=utf8 COLLATE=utf8_general_ci;"
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->createTable($table));
    }
    
    public function testNoPrimaryKey()
    {
        $table = new Table('no_primary_key');
        $table->setCharset('utf16');
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('title', 'string', ['null' => true])));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('total', 'integer', ['default' => 0])));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('is_deleted', 'boolean', ['default' => false])));
        
        $queryBuilder = new MysqlQueryBuilder();
        $expectedQueries = [
            "CREATE TABLE `no_primary_key` (`title` varchar(255) DEFAULT NULL,`total` int(11) NOT NULL DEFAULT 0,`is_deleted` tinyint(1) NOT NULL DEFAULT 0) DEFAULT CHARACTER SET=utf16;"
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->createTable($table));
    }
    
    public function testOwnPrimaryKey()
    {
        $table = new Table('own_primary_key');
        $table->addPrimary(new Column('identifier', 'string', ['length' => 32]));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('title', 'string', ['default' => ''])));
        
        $queryBuilder = new MysqlQueryBuilder();
        $expectedQueries = [
            "CREATE TABLE `own_primary_key` (`identifier` varchar(32) NOT NULL,`title` varchar(255) NOT NULL DEFAULT '',PRIMARY KEY (`identifier`));"
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->createTable($table));
    }
    
    public function testMoreOwnPrimaryKeys()
    {
        $table = new Table('more_own_primary_keys');
        $table->addPrimary([new Column('identifier', 'string', ['length' => 32]), new Column('subidentifier', 'string', ['length' => 32])]);
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('title', 'string', ['default' => ''])));
        
        $queryBuilder = new MysqlQueryBuilder();
        $expectedQueries = [
            "CREATE TABLE `more_own_primary_keys` (`identifier` varchar(32) NOT NULL,`subidentifier` varchar(32) NOT NULL,`title` varchar(255) NOT NULL DEFAULT '',PRIMARY KEY (`identifier`,`subidentifier`));"
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->createTable($table));
    }
    
    public function testOneFieldAsPrimaryKey()
    {
        $table = new Table('one_field_as_pk');
        $table->addPrimary('identifier');
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('identifier', 'string', ['length' => 32])));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('title', 'string', ['default' => ''])));
        
        $queryBuilder = new MysqlQueryBuilder();
        $expectedQueries = [
            "CREATE TABLE `one_field_as_pk` (`identifier` varchar(32) NOT NULL,`title` varchar(255) NOT NULL DEFAULT '',PRIMARY KEY (`identifier`));"
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
        
        $queryBuilder = new MysqlQueryBuilder();
        $expectedQueries = [
            "CREATE TABLE `more_fields_as_pk` (`identifier` varchar(32) NOT NULL,`subidentifier` varchar(32) NOT NULL,`title` varchar(255) NOT NULL DEFAULT '',PRIMARY KEY (`identifier`,`subidentifier`));"
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
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addIndex(new Index('sorting', 'sorting', '', 'btree')));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addIndex(new Index(['title', 'alias'], 'title_alias', 'unique')));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addIndex(new Index('bodytext', 'bodytext', 'fulltext', 'hash')));
        
        $queryBuilder = new MysqlQueryBuilder();
        $expectedQueries = [
            "CREATE TABLE `table_with_indexes` (`id` int(11) NOT NULL AUTO_INCREMENT,`title` varchar(255) NOT NULL,`alias` varchar(255) NOT NULL,`sorting` int(11) NOT NULL,`bodytext` text NOT NULL,PRIMARY KEY (`id`),INDEX `sorting` (`sorting`) USING BTREE,UNIQUE INDEX `title_alias` (`title`,`alias`),FULLTEXT INDEX `bodytext` (`bodytext`) USING HASH);"
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
        
        $queryBuilder = new MysqlQueryBuilder();
        $expectedQueries = [
            "CREATE TABLE `table_with_foreign_keys` (`id` int(11) NOT NULL AUTO_INCREMENT,`title` varchar(255) NOT NULL,`alias` varchar(255) NOT NULL,`foreign_table_id` int(11) NOT NULL,PRIMARY KEY (`id`),CONSTRAINT `table_with_foreign_keys_foreign_table_id` FOREIGN KEY (`foreign_table_id`) REFERENCES `second_table` (`id`));"
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
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addIndex(new Index('sorting', 'sorting', '', 'btree')));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addIndex(new Index(['title', 'alias'], 'title_alias', 'unique')));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addIndex(new Index('bodytext', 'bodytext', 'fulltext', 'hash')));
        
        $queryBuilder = new MysqlQueryBuilder();
        $expectedQueries = [
            "CREATE TABLE `table_with_indexes_and_foreign_keys` (`id` int(11) NOT NULL AUTO_INCREMENT,`title` varchar(255) NOT NULL,`alias` varchar(255) NOT NULL,`sorting` int(11) NOT NULL,`bodytext` text NOT NULL,`foreign_table_id` int(11) NOT NULL,PRIMARY KEY (`id`),INDEX `sorting` (`sorting`) USING BTREE,UNIQUE INDEX `title_alias` (`title`,`alias`),FULLTEXT INDEX `bodytext` (`bodytext`) USING HASH,CONSTRAINT `table_with_indexes_and_foreign_keys_foreign_table_id` FOREIGN KEY (`foreign_table_id`) REFERENCES `second_table` (`foreign_id`) ON DELETE SET NULL ON UPDATE SET NULL);"
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->createTable($table));
    }
    
    public function testDropTable()
    {
        $table = new Table('drop');
        $queryBuilder = new MysqlQueryBuilder();
        $expectedQueries = [
            'DROP TABLE `drop`'
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->dropTable($table));
    }
    
    public function testAlterTable()
    {
        // add columns
        $table = new Table('add_columns');
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('title', 'string')));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('alias', 'string')));
        
        $queryBuilder = new MysqlQueryBuilder();
        $expectedQueries = [
            'ALTER TABLE `add_columns` ADD COLUMN `title` varchar(255) NOT NULL,ADD COLUMN `alias` varchar(255) NOT NULL;'
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->alterTable($table));
        
        // add and remove primary key
        $table = new Table('change_primary_key');
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->dropPrimaryKey());
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addPrimary('new_primary'));
        
        $queryBuilder = new MysqlQueryBuilder();
        $expectedQueries = [
            'ALTER TABLE `change_primary_key` DROP PRIMARY KEY;',
            'ALTER TABLE `change_primary_key` ADD PRIMARY KEY (`new_primary`);',
            
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->alterTable($table));
        
        // add index
        $table = new Table('add_index');
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addIndex(new Index('alias', 'alias', 'unique')));
        
        $queryBuilder = new MysqlQueryBuilder();
        $expectedQueries = [
            'ALTER TABLE `add_index` ADD UNIQUE INDEX `alias` (`alias`);',
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->alterTable($table));
        
        // add column and index
        $table = new Table('add_column_and_index');
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('alias', 'string')));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addIndex(new Index('alias', 'alias', 'unique')));
        
        $queryBuilder = new MysqlQueryBuilder();
        $expectedQueries = [
            'ALTER TABLE `add_column_and_index` ADD COLUMN `alias` varchar(255) NOT NULL;',
            'ALTER TABLE `add_column_and_index` ADD UNIQUE INDEX `alias` (`alias`);',
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->alterTable($table));
        
        // add foreign key, index, columns
        $table = new Table('add_columns_index_foreign_key');
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('foreign_key_id', 'integer')));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('sorting', 'integer')));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addIndex(new Index('sorting', 'sorting')));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addForeignKey(new ForeignKey('foreign_key_id', 'referenced_table')));
        
        $queryBuilder = new MysqlQueryBuilder();
        $expectedQueries = [
            'ALTER TABLE `add_columns_index_foreign_key` ADD COLUMN `foreign_key_id` int(11) NOT NULL,ADD COLUMN `sorting` int(11) NOT NULL;',
            'ALTER TABLE `add_columns_index_foreign_key` ADD INDEX `sorting` (`sorting`);',
            'ALTER TABLE `add_columns_index_foreign_key` ADD CONSTRAINT `add_columns_index_foreign_key_foreign_key_id` FOREIGN KEY (`foreign_key_id`) REFERENCES `referenced_table` (`id`);',
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->alterTable($table));
        
        // remove columns
        
        // remove index
        
        // remove foreign key
        
        // combination of add / remove column, add / remove index, add / remove foreign key
        $table = new Table('all_in_one');
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('foreign_key_id', 'integer', ['after' => 'column_before'])));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('sorting', 'integer')));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->dropColumn('title'));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addIndex(new Index('sorting', 'sorting')));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->dropIndex('alias'));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addForeignKey(new ForeignKey('foreign_key_id', 'referenced_table')));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->dropForeignKey('foreign_key_to_drop_id'));
        
        $queryBuilder = new MysqlQueryBuilder();
        $expectedQueries = [
            'ALTER TABLE `all_in_one` DROP INDEX `alias`;',
            'ALTER TABLE `all_in_one` DROP FOREIGN KEY `all_in_one_foreign_key_to_drop_id`;',
            'ALTER TABLE `all_in_one` DROP COLUMN `title`;',
            'ALTER TABLE `all_in_one` ADD COLUMN `foreign_key_id` int(11) NOT NULL AFTER `column_before`,ADD COLUMN `sorting` int(11) NOT NULL;',
            'ALTER TABLE `all_in_one` ADD INDEX `sorting` (`sorting`);',
            'ALTER TABLE `all_in_one` ADD CONSTRAINT `all_in_one_foreign_key_id` FOREIGN KEY (`foreign_key_id`) REFERENCES `referenced_table` (`id`);',
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->alterTable($table));
        
        // mixed order of calls add / remove column, add / remove index, add / remove foreign key - output is the same
        $table = new Table('all_in_one_mixed');
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addIndex(new Index('sorting', 'sorting')));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->dropForeignKey('foreign_key_to_drop_id'));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('foreign_key_id', 'integer', ['first' => true])));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->dropColumn('title'));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('sorting', 'integer')));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->dropIndex('alias'));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addForeignKey(new ForeignKey('foreign_key_id', 'referenced_table')));
                
        $queryBuilder = new MysqlQueryBuilder();
        $expectedQueries = [
            'ALTER TABLE `all_in_one_mixed` DROP INDEX `alias`;',
            'ALTER TABLE `all_in_one_mixed` DROP FOREIGN KEY `all_in_one_mixed_foreign_key_to_drop_id`;',
            'ALTER TABLE `all_in_one_mixed` DROP COLUMN `title`;',
            'ALTER TABLE `all_in_one_mixed` ADD COLUMN `foreign_key_id` int(11) NOT NULL FIRST,ADD COLUMN `sorting` int(11) NOT NULL;',
            'ALTER TABLE `all_in_one_mixed` ADD INDEX `sorting` (`sorting`);',
            'ALTER TABLE `all_in_one_mixed` ADD CONSTRAINT `all_in_one_mixed_foreign_key_id` FOREIGN KEY (`foreign_key_id`) REFERENCES `referenced_table` (`id`);',
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->alterTable($table));
    }
    
    public function testChangeColumn()
    {
        $table = new Table('with_columns_to_change');
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->changeColumn('old_name', new Column('new_name', 'integer')));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->changeColumn('no_name_change', new Column('no_name_change', 'integer')));
        
        $queryBuilder = new MysqlQueryBuilder();
        $expectedQueries = [
            'ALTER TABLE `with_columns_to_change` CHANGE COLUMN `old_name` `new_name` int(11) NOT NULL,CHANGE COLUMN `no_name_change` `no_name_change` int(11) NOT NULL;',
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->alterTable($table));
    }
    
    public function testChangeAddedColumn()
    {
        $table = new Table('with_change_added_column');
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn(new Column('old_name', 'integer')));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->changeColumn('old_name', new Column('new_name', 'string')));
        
        $queryBuilder = new MysqlQueryBuilder();
        $expectedQueries = [
            'ALTER TABLE `with_change_added_column` ADD COLUMN `new_name` varchar(255) NOT NULL;',
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->alterTable($table));
    }
    
    public function testRenameTable()
    {
        $table = new Table('old_table_name');
        $queryBuilder = new MysqlQueryBuilder();
        $expectedQueries = [
            'RENAME TABLE `old_table_name` TO `new_table_name`;',
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->renameTable($table, 'new_table_name'));
    }
}
