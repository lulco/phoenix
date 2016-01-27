<?php

namespace Phoenix\Tests\Mock\Database\Adapter;

use Phoenix\Database\QueryBuilder\SqliteQueryBuilder;

class DummySqliteAdapter extends DummyAdapter
{
    public function getQueryBuilder()
    {
        return new SqliteQueryBuilder();
    }
}
