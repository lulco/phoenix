<?php

namespace Phoenix\Tests\Mock\Database\Adapter;

use Phoenix\Database\QueryBuilder\MysqlQueryBuilder;

class DummyMysqlAdapter extends DummyAdapter
{
    public function getQueryBuilder()
    {
        return new MysqlQueryBuilder();
    }
}
