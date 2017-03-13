<?php

namespace Phoenix\Tests\Database\Adapter;

use Phoenix\Database\Adapter\MysqlAdapter;
use Phoenix\Database\Element\MigrationTable;
use Phoenix\Database\Element\Structure;
use Phoenix\Database\Element\Table;
use Phoenix\Database\QueryBuilder\QueryBuilderInterface;
use Phoenix\Tests\Helpers\Adapter\MysqlCleanupAdapter;
use Phoenix\Tests\Helpers\Pdo\MysqlPdo;
use PHPUnit_Framework_TestCase;

class MysqlAdapterTest extends PHPUnit_Framework_TestCase
{
    private $adapter;

    public function setUp()
    {
        $pdo = new MysqlPdo();
        $adapter = new MysqlCleanupAdapter($pdo);
        $adapter->cleanupDatabase();

        $pdo = new MysqlPdo(getenv('PHOENIX_MYSQL_DATABASE'));
        $this->adapter = new MysqlAdapter($pdo);
    }

    public function testGetQueryBuilder()
    {
        $this->assertInstanceOf(QueryBuilderInterface::class, $this->adapter->getQueryBuilder());
    }

    public function testGetEmptyStructureAndUpdate()
    {
        $structure = $this->adapter->getStructure();
        $this->assertInstanceOf(Structure::class, $structure);
        $this->assertEmpty($structure->getTables());
        $this->assertNull($structure->getTable('structure_test'));

        $migrationTable = new MigrationTable('structure_test');
        $migrationTable->addColumn('title', 'string');
        $migrationTable->create();
        $structure->update($migrationTable);

        $this->assertCount(1, $structure->getTables());
        $this->assertInstanceOf(Table::class, $structure->getTable('structure_test'));

        $updatedStructure = $this->adapter->getStructure();
        $this->assertInstanceOf(Structure::class, $updatedStructure);
        $this->assertCount(1, $updatedStructure->getTables());
        $this->assertInstanceOf(Table::class, $structure->getTable('structure_test'));
    }

    public function testGetNonEmptyStructureAndUpdate()
    {
        $this->adapter->execute("CREATE TABLE `structure_test` (
  `identifier` char(36) NOT NULL,
  `col_integer` int(11) NOT NULL,
  `col_bigint` bigint(20) NOT NULL,
  `col_string` varchar(255) NOT NULL,
  `col_char` char(255) NOT NULL,
  `col_text` text NOT NULL,
  `col_json` text NOT NULL,
  `col_float` float(10,3) NOT NULL,
  `col_decimal` decimal(10,3) NOT NULL,
  `col_boolean` tinyint(1) NOT NULL,
  `col_datetime` datetime NOT NULL,
  `col_date` date NOT NULL,
  `col_enum` enum('xxx','yyy','zzz','qqq') DEFAULT NULL,
  `col_set` set('xxx','yyy','zzz','qqq') DEFAULT NULL,
  PRIMARY KEY (`identifier`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

        $structure = $this->adapter->getStructure();
        $this->assertInstanceOf(Structure::class, $structure);
        $this->assertCount(1, $structure->getTables());
        $this->assertInstanceOf(Table::class, $structure->getTable('structure_test'));
    }
}
