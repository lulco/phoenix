<?php

namespace Phoenix\Database\Adapter;

use LogicException;
use Phoenix\Database\QueryBuilder\MysqlQueryBuilder;

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
    
    public function tableInfo($table)
    {
        throw new LogicException('Not yet implemented');
    }
}
