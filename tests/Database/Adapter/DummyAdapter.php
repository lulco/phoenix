<?php

namespace Phoenix\Tests\Database\Adapter;

use Phoenix\Database\Adapter\AdapterInterface;
use Phoenix\QueryBuilder\MysqlQueryBuilder;

class DummyAdapter implements AdapterInterface
{
    public function execute($sql)
    {
        return 'Query ' . $sql . ' executed';
    }

    public function getQueryBuilder()
    {
        return new MysqlQueryBuilder();
    }
}
