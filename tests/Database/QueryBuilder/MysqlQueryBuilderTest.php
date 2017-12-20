<?php

namespace Phoenix\Tests\Database\QueryBuilder;

use Phoenix\Database\Adapter\MysqlAdapter;
use Phoenix\Database\Element\Column;
use Phoenix\Database\Element\MigrationTable;
use Phoenix\Database\QueryBuilder\MysqlQueryBuilder;
use Phoenix\Tests\Helpers\Pdo\MysqlPdo;
use PHPUnit\Framework\TestCase;

class MysqlQueryBuilderTest extends TestCase
{
    private $adapter;

    protected function setUp()
    {
        $pdo = new MysqlPdo(getenv('PHOENIX_MYSQL_DATABASE'));
        $this->adapter = new MysqlAdapter($pdo);
    }

    public function testSimpleCreate()
    {
        $table = new MigrationTable('simple');
        $table->addPrimary(true);
        $table->setCharset('utf8');
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('title', 'string'));

        $queryBuilder = new MysqlQueryBuilder($this->adapter);
        $expectedQueries = [
            'CREATE TABLE `simple` (`id` int(11) NOT NULL AUTO_INCREMENT,`title` varchar(255) NOT NULL,PRIMARY KEY (`id`)) DEFAULT CHARACTER SET=utf8;'
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->createTable($table));
    }

    public function testMoreColumns()
    {
        $table = new MigrationTable('more_columns');
        $table->addPrimary(true);
        $table->setCharset('utf8');
        $table->setCollation('utf8_general_ci');
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('title', 'string', ['charset' => 'utf16']));
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('alias', 'string', ['null' => true]));
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('total', 'integer', ['default' => 0]));
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('bodytext', 'text', ['collation' => 'utf8_slovak_ci']));
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('price', 'decimal', ['length' => 8, 'decimals' => 2]));

        $queryBuilder = new MysqlQueryBuilder($this->adapter);
        $expectedQueries = [
            "CREATE TABLE `more_columns` (`id` int(11) NOT NULL AUTO_INCREMENT,`title` varchar(255) CHARACTER SET utf16 NOT NULL,`alias` varchar(255) DEFAULT NULL,`total` int(11) NOT NULL DEFAULT 0,`bodytext` text COLLATE utf8_slovak_ci NOT NULL,`price` decimal(8,2) NOT NULL,PRIMARY KEY (`id`)) DEFAULT CHARACTER SET=utf8 COLLATE=utf8_general_ci;"
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

        $queryBuilder = new MysqlQueryBuilder($this->adapter);
        $expectedQueries = [
            "CREATE TABLE `all_types` (`id` int(11) NOT NULL AUTO_INCREMENT,`col_uuid` char(36) NOT NULL,`col_tinyint` tinyint(4) NOT NULL,`col_smallint` smallint(6) NOT NULL,`col_mediumint` mediumint(9) NOT NULL,`col_int` int(11) unsigned NOT NULL,`col_bigint` bigint(20) NOT NULL,`col_string` varchar(255) NOT NULL,`col_char` char(255) NOT NULL,`col_binary` binary(255) NOT NULL,`col_varbinary` varbinary(255) NOT NULL,`col_tinytext` tinytext NOT NULL,`col_mediumtext` mediumtext NOT NULL,`col_text` text NOT NULL,`col_longtext` longtext NOT NULL,`col_tinyblob` tinyblob NOT NULL,`col_mediumblob` mediumblob NOT NULL,`col_blob` blob NOT NULL,`col_longblob` longblob NOT NULL,`col_json` text NOT NULL,`col_numeric` decimal(10,3) NOT NULL,`col_decimal` decimal(10,3) NOT NULL,`col_float` float(10,3) NOT NULL,`col_double` double(10,3) NOT NULL,`col_boolean` tinyint(1) NOT NULL,`col_datetime` datetime NOT NULL,`col_date` date NOT NULL,`col_enum` enum('xxx','yyy','zzz') NOT NULL,`col_set` set('xxx','yyy','zzz') NOT NULL,`col_point` point DEFAULT NULL,`col_line` linestring DEFAULT NULL,`col_polygon` polygon DEFAULT NULL,PRIMARY KEY (`id`));"
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->createTable($table));
    }

    public function testNoPrimaryKey()
    {
        $table = new MigrationTable('no_primary_key');
        $table->setCharset('utf16');
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('title', 'string', ['null' => true]));
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('total', 'integer', ['default' => 0]));
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('is_deleted', 'boolean', ['default' => false]));

        $queryBuilder = new MysqlQueryBuilder($this->adapter);
        $expectedQueries = [
            "CREATE TABLE `no_primary_key` (`title` varchar(255) DEFAULT NULL,`total` int(11) NOT NULL DEFAULT 0,`is_deleted` tinyint(1) NOT NULL DEFAULT 0) DEFAULT CHARACTER SET=utf16;"
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->createTable($table));
    }

    public function testOwnPrimaryKey()
    {
        $table = new MigrationTable('own_primary_key');
        $table->addPrimary(new Column('identifier', 'string', ['length' => 32]));
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('title', 'string', ['default' => '']));

        $queryBuilder = new MysqlQueryBuilder($this->adapter);
        $expectedQueries = [
            "CREATE TABLE `own_primary_key` (`identifier` varchar(32) NOT NULL,`title` varchar(255) NOT NULL DEFAULT '',PRIMARY KEY (`identifier`));"
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->createTable($table));
    }

    public function testMoreOwnPrimaryKeys()
    {
        $table = new MigrationTable('more_own_primary_keys');
        $table->addPrimary([new Column('identifier', 'string', ['length' => 32]), new Column('subidentifier', 'string', ['length' => 32])]);
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('title', 'string', ['default' => '']));

        $queryBuilder = new MysqlQueryBuilder($this->adapter);
        $expectedQueries = [
            "CREATE TABLE `more_own_primary_keys` (`identifier` varchar(32) NOT NULL,`subidentifier` varchar(32) NOT NULL,`title` varchar(255) NOT NULL DEFAULT '',PRIMARY KEY (`identifier`,`subidentifier`));"
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->createTable($table));
    }

    public function testOneFieldAsPrimaryKey()
    {
        $table = new MigrationTable('one_field_as_pk');
        $table->addPrimary('identifier');
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('identifier', 'string', ['length' => 32]));
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('title', 'string', ['default' => '']));

        $queryBuilder = new MysqlQueryBuilder($this->adapter);
        $expectedQueries = [
            "CREATE TABLE `one_field_as_pk` (`identifier` varchar(32) NOT NULL,`title` varchar(255) NOT NULL DEFAULT '',PRIMARY KEY (`identifier`));"
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

        $queryBuilder = new MysqlQueryBuilder($this->adapter);
        $expectedQueries = [
            "CREATE TABLE `more_fields_as_pk` (`identifier` varchar(32) NOT NULL,`subidentifier` varchar(32) NOT NULL,`title` varchar(255) NOT NULL DEFAULT '',PRIMARY KEY (`identifier`,`subidentifier`));"
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->createTable($table));
    }

    public function testCreateTableWithCommentOnColumn()
    {
        $table = new MigrationTable('table_with_column_comment');
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('column_without_comment', 'string'));
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('column_with_comment', 'string', ['comment' => 'My comment']));
        $table->create();

        $queryBuilder = new MysqlQueryBuilder($this->adapter);
        $expectedQueries = [
            "CREATE TABLE `table_with_column_comment` (`id` int(11) NOT NULL AUTO_INCREMENT,`column_without_comment` varchar(255) NOT NULL,`column_with_comment` varchar(255) NOT NULL COMMENT 'My comment',PRIMARY KEY (`id`));"
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->createTable($table));
    }

    public function testAddCommentToExistingColumn()
    {
        $table = new MigrationTable('table_with_column_comment');
        $this->assertInstanceOf(MigrationTable::class, $table->changeColumn('column_to_comment', 'column_to_comment', 'string', ['comment' => 'My comment']));
        $table->save();

        $queryBuilder = new MysqlQueryBuilder($this->adapter);
        $expectedQueries = [
            "ALTER TABLE `table_with_column_comment` CHANGE COLUMN `column_to_comment` `column_to_comment` varchar(255) NOT NULL COMMENT 'My comment';",
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
        $this->assertInstanceOf(MigrationTable::class, $table->addIndex('sorting', '', 'btree', 'sorting'));
        $this->assertInstanceOf(MigrationTable::class, $table->addIndex(['title', 'alias'], 'unique', '', 'title_alias'));
        $this->assertInstanceOf(MigrationTable::class, $table->addIndex('bodytext', 'fulltext', 'hash', 'bodytext'));

        $queryBuilder = new MysqlQueryBuilder($this->adapter);
        $expectedQueries = [
            "CREATE TABLE `table_with_indexes` (`id` int(11) NOT NULL AUTO_INCREMENT,`title` varchar(255) NOT NULL,`alias` varchar(255) NOT NULL,`sorting` int(11) NOT NULL,`bodytext` text NOT NULL,PRIMARY KEY (`id`),INDEX `sorting` (`sorting`) USING BTREE,UNIQUE INDEX `title_alias` (`title`,`alias`),FULLTEXT INDEX `bodytext` (`bodytext`) USING HASH);"
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

        $queryBuilder = new MysqlQueryBuilder($this->adapter);
        $expectedQueries = [
            "CREATE TABLE `table_with_foreign_keys` (`id` int(11) NOT NULL AUTO_INCREMENT,`title` varchar(255) NOT NULL,`alias` varchar(255) NOT NULL,`foreign_table_id` int(11) NOT NULL,PRIMARY KEY (`id`),CONSTRAINT `table_with_foreign_keys_foreign_table_id` FOREIGN KEY (`foreign_table_id`) REFERENCES `second_table` (`id`));"
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
        $this->assertInstanceOf(MigrationTable::class, $table->addIndex('sorting', '', 'btree', 'sorting'));
        $this->assertInstanceOf(MigrationTable::class, $table->addIndex(['title', 'alias'], 'unique', '', 'title_alias'));
        $this->assertInstanceOf(MigrationTable::class, $table->addIndex('bodytext', 'fulltext', 'hash', 'bodytext'));

        $queryBuilder = new MysqlQueryBuilder($this->adapter);
        $expectedQueries = [
            "CREATE TABLE `table_with_indexes_and_foreign_keys` (`id` int(11) NOT NULL AUTO_INCREMENT,`title` varchar(255) NOT NULL,`alias` varchar(255) NOT NULL,`sorting` int(11) NOT NULL,`bodytext` text NOT NULL,`foreign_table_id` int(11) NOT NULL,PRIMARY KEY (`id`),INDEX `sorting` (`sorting`) USING BTREE,UNIQUE INDEX `title_alias` (`title`,`alias`),FULLTEXT INDEX `bodytext` (`bodytext`) USING HASH,CONSTRAINT `table_with_indexes_and_foreign_keys_foreign_table_id` FOREIGN KEY (`foreign_table_id`) REFERENCES `second_table` (`foreign_id`) ON DELETE SET NULL ON UPDATE SET NULL);"
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->createTable($table));
    }

    public function testDropMigrationTable()
    {
        $table = new MigrationTable('drop');
        $queryBuilder = new MysqlQueryBuilder($this->adapter);
        $expectedQueries = [
            'DROP TABLE `drop`'
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->dropTable($table));
    }

    public function testAlterMigrationTable()
    {
        // add columns
        $table = new MigrationTable('add_columns');
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('title', 'string'));
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('alias', 'string'));

        $queryBuilder = new MysqlQueryBuilder($this->adapter);
        $expectedQueries = [
            'ALTER TABLE `add_columns` ADD COLUMN `title` varchar(255) NOT NULL,ADD COLUMN `alias` varchar(255) NOT NULL;'
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->alterTable($table));

        // add and remove primary key
        $table = new MigrationTable('change_primary_key');
        $this->assertInstanceOf(MigrationTable::class, $table->dropPrimaryKey());
        $this->assertInstanceOf(MigrationTable::class, $table->addPrimary('new_primary'));

        $queryBuilder = new MysqlQueryBuilder($this->adapter);
        $expectedQueries = [
            'ALTER TABLE `change_primary_key` DROP PRIMARY KEY;',
            'ALTER TABLE `change_primary_key` ADD PRIMARY KEY (`new_primary`);',

        ];
        $this->assertEquals($expectedQueries, $queryBuilder->alterTable($table));

        // add index
        $table = new MigrationTable('add_index');
        $this->assertInstanceOf(MigrationTable::class, $table->addIndex('alias', 'unique', '', 'alias'));

        $queryBuilder = new MysqlQueryBuilder($this->adapter);
        $expectedQueries = [
            'ALTER TABLE `add_index` ADD UNIQUE INDEX `alias` (`alias`);',
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->alterTable($table));

        // add column and index
        $table = new MigrationTable('add_column_and_index');
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('alias', 'string'));
        $this->assertInstanceOf(MigrationTable::class, $table->addIndex('alias', 'unique', '', 'alias'));

        $queryBuilder = new MysqlQueryBuilder($this->adapter);
        $expectedQueries = [
            'ALTER TABLE `add_column_and_index` ADD COLUMN `alias` varchar(255) NOT NULL;',
            'ALTER TABLE `add_column_and_index` ADD UNIQUE INDEX `alias` (`alias`);',
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->alterTable($table));

        // add foreign key, index, columns
        $table = new MigrationTable('add_columns_index_foreign_key');
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('foreign_key_id', 'integer'));
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('sorting', 'integer'));
        $this->assertInstanceOf(MigrationTable::class, $table->addIndex('sorting', '', '', 'sorting'));
        $this->assertInstanceOf(MigrationTable::class, $table->addForeignKey('foreign_key_id', 'referenced_table'));

        $queryBuilder = new MysqlQueryBuilder($this->adapter);
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
        $table = new MigrationTable('all_in_one');
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('foreign_key_id', 'integer', ['after' => 'column_before']));
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('sorting', 'integer'));
        $this->assertInstanceOf(MigrationTable::class, $table->dropColumn('title'));
        $this->assertInstanceOf(MigrationTable::class, $table->addIndex('sorting', '', '', 'sorting'));
        $this->assertInstanceOf(MigrationTable::class, $table->dropIndex('alias'));
        $this->assertInstanceOf(MigrationTable::class, $table->addForeignKey('foreign_key_id', 'referenced_table'));
        $this->assertInstanceOf(MigrationTable::class, $table->dropForeignKey('foreign_key_to_drop_id'));

        $queryBuilder = new MysqlQueryBuilder($this->adapter);
        $expectedQueries = [
            'ALTER TABLE `all_in_one` DROP INDEX `idx_all_in_one_alias`;',
            'ALTER TABLE `all_in_one` DROP FOREIGN KEY `all_in_one_foreign_key_to_drop_id`;',
            'ALTER TABLE `all_in_one` DROP COLUMN `title`;',
            'ALTER TABLE `all_in_one` ADD COLUMN `foreign_key_id` int(11) NOT NULL AFTER `column_before`,ADD COLUMN `sorting` int(11) NOT NULL;',
            'ALTER TABLE `all_in_one` ADD INDEX `sorting` (`sorting`);',
            'ALTER TABLE `all_in_one` ADD CONSTRAINT `all_in_one_foreign_key_id` FOREIGN KEY (`foreign_key_id`) REFERENCES `referenced_table` (`id`);',
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->alterTable($table));

        // mixed order of calls add / remove column, add / remove index, add / remove foreign key - output is the same
        $table = new MigrationTable('all_in_one_mixed');
        $this->assertInstanceOf(MigrationTable::class, $table->addIndex('sorting', '', '', 'sorting'));
        $this->assertInstanceOf(MigrationTable::class, $table->dropForeignKey('foreign_key_to_drop_id'));
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('foreign_key_id', 'integer', ['first' => true]));
        $this->assertInstanceOf(MigrationTable::class, $table->dropColumn('title'));
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('sorting', 'integer'));
        $this->assertInstanceOf(MigrationTable::class, $table->dropIndexByName('alias'));
        $this->assertInstanceOf(MigrationTable::class, $table->addForeignKey('foreign_key_id', 'referenced_table'));

        $queryBuilder = new MysqlQueryBuilder($this->adapter);
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
        $table = new MigrationTable('with_columns_to_change');
        $this->assertInstanceOf(MigrationTable::class, $table->changeColumn('old_name', 'new_name', 'integer'));
        $this->assertInstanceOf(MigrationTable::class, $table->changeColumn('no_name_change', 'no_name_change', 'integer'));

        $queryBuilder = new MysqlQueryBuilder($this->adapter);
        $expectedQueries = [
            'ALTER TABLE `with_columns_to_change` CHANGE COLUMN `old_name` `new_name` int(11) NOT NULL,CHANGE COLUMN `no_name_change` `no_name_change` int(11) NOT NULL;',
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->alterTable($table));
    }

    public function testChangeAddedColumn()
    {
        $table = new MigrationTable('with_change_added_column');
        $this->assertInstanceOf(MigrationTable::class, $table->addColumn('old_name', 'integer'));
        $this->assertInstanceOf(MigrationTable::class, $table->changeColumn('old_name', 'new_name', 'string'));

        $queryBuilder = new MysqlQueryBuilder($this->adapter);
        $expectedQueries = [
            'ALTER TABLE `with_change_added_column` ADD COLUMN `new_name` varchar(255) NOT NULL;',
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->alterTable($table));
    }

    public function testRenameMigrationTable()
    {
        $table = new MigrationTable('old_table_name');
        $table->rename('new_table_name');
        $queryBuilder = new MysqlQueryBuilder($this->adapter);
        $expectedQueries = [
            'RENAME TABLE `old_table_name` TO `new_table_name`;',
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->renameTable($table));
    }

    public function testCreateTableWithComment()
    {
        $table = new MigrationTable('table_with_comment');
        $table->setComment('test table with comment');
        $table->addColumn('title', 'string');
        $table->create();
        $queryBuilder = new MysqlQueryBuilder($this->adapter);
        $expectedQueries = [
            "CREATE TABLE `table_with_comment` (`id` int(11) NOT NULL AUTO_INCREMENT,`title` varchar(255) NOT NULL,PRIMARY KEY (`id`)) COMMENT='test table with comment';"
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->createTable($table));
    }

    public function testAddCommentToExistingTable()
    {
        $table = new MigrationTable('table_with_comment');
        $table->setComment('test table with comment');
        $table->save();
        $queryBuilder = new MysqlQueryBuilder($this->adapter);
        $expectedQueries = [
            "ALTER TABLE `table_with_comment` COMMENT='test table with comment';"
        ];
        $this->assertEquals($expectedQueries, $queryBuilder->alterTable($table));
    }
}
