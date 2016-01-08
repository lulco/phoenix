<?php

namespace Phoenix\Database\Adapter;

use Phoenix\QueryBuilder\SqliteQueryBuilder;

class SqliteAdapter extends PdoAdapter
{
    /**
     * @return SqliteQueryBuilder
     */
    public function getQueryBuilder()
    {
        return new SqliteQueryBuilder();
    }
}
