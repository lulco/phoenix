<?php

namespace Phoenix\Tests\Database\Adapter;

use Phoenix\QueryBuilder\PgsqlQueryBuilder;

class DummyPgsqlAdapter extends DummyAdapter
{
    public function getQueryBuilder()
    {
        return new PgsqlQueryBuilder();
    }
}
