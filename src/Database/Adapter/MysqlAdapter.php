<?php

namespace Phoenix\Database\Adapter;

use Phoenix\QueryBuilder\MysqlQueryBuilder;

class MysqlAdapter extends PdoAdapter
{
    /**
     * @return MysqlQueryBuilder
     */
    public function getQueryBuilder()
    {
        if (!$this->queryBuilder) {
            $this->queryBuilder = new MysqlQueryBuilder();
        }
        return $this->queryBuilder;
    }
}
