<?php

namespace Phoenix\Tests\Database\Adapter;

use Phoenix\QueryBuilder\MysqlQueryBuilder;

class DummyMysqlAdapter extends DummyAdapter
{
    public function getQueryBuilder()
    {
        return new MysqlQueryBuilder();
    }
}
