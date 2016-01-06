<?php

namespace Phoenix\Database\Adapter;

use Phoenix\QueryBuilder\MysqlQueryBuilder;

class MysqlAdapter extends PdoAdapter
{
    public function getQueryBuilder()
    {
        return new MysqlQueryBuilder();
    }
}
