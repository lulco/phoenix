<?php

namespace Phoenix\Tests;

use Phoenix\Tests\Database\FakePdo;
use Phoenix\Database\Adapter\MysqlAdapter;
use PHPUnit_Framework_TestCase;

class MysqlAdapterTest extends PHPUnit_Framework_TestCase
{
    public function testSimple()
    {
        $pdo = new FakePdo();
        $adapter = new MysqlAdapter($pdo);
        $this->assertInstanceOf('\Phoenix\QueryBuilder\QueryBuilderInterface', $adapter->getQueryBuilder());
    }
}
