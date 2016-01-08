<?php

namespace Phoenix\Tests;

use Phoenix\Tests\Database\FakePdo;
use Phoenix\Database\Adapter\SqliteAdapter;
use PHPUnit_Framework_TestCase;

class SqliteAdapterTest extends PHPUnit_Framework_TestCase
{
    public function testSimple()
    {
        $pdo = new FakePdo();
        $adapter = new SqliteAdapter($pdo);
        $this->assertInstanceOf('\Phoenix\QueryBuilder\QueryBuilderInterface', $adapter->getQueryBuilder());
    }
}
