<?php

namespace Phoenix\Database\Adapter;

use Phoenix\QueryBuilder\PgsqlQueryBuilder;

class PgsqlAdapter extends PdoAdapter
{
    /**
     * @return PgsqlQueryBuilder
     */
    public function getQueryBuilder()
    {
        return new PgsqlQueryBuilder();
    }
}
