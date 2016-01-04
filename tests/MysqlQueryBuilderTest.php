<?php

namespace Phoenix\Tests;

use Phoenix\QueryBuilder\MysqlQueryBuilder;
use Phoenix\QueryBuilder\Table;
use PHPUnit_Framework_TestCase;

class MysqlQueryBuilderTest extends PHPUnit_Framework_TestCase
{
    public function testSimpleCreate()
    {
        $table = new Table('test');
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addColumn('title', 'string'));
        
        $queryCreator = new MysqlQueryBuilder($table);
        $expectedQuery = 'CREATE TABLE `test` (`id` int(11) NOT NULL AUTO_INCREMENT,`title` varchar(255) NOT NULL,PRIMARY KEY (`id`)) DEFAULT CHARACTER SET=utf8 COLLATE=utf8_general_ci;';
        $this->assertEquals($expectedQuery, $queryCreator->create());
    }
}
