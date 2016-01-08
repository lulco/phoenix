<?php

namespace Phoenix\Tests\Database\Adapter;

use Phoenix\QueryBuilder\SqliteQueryBuilder;

class DummySqliteAdapter extends DummyAdapter
{
    public function getQueryBuilder()
    {
        return new SqliteQueryBuilder();
    }
}
