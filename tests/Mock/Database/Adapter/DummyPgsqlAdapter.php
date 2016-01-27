<?php

namespace Phoenix\Tests\Mock\Database\Adapter;

use Phoenix\Database\QueryBuilder\PgsqlQueryBuilder;

class DummyPgsqlAdapter extends DummyAdapter
{
    public function getQueryBuilder()
    {
        return new PgsqlQueryBuilder();
    }
}
