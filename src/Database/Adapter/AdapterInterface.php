<?php

namespace Phoenix\Database\Adapter;

use Phoenix\QueryBuilder\QueryBuilderInterface;

interface AdapterInterface
{
    /**
     * @param mixed $sql
     */
    public function execute($sql);
    
    /**
     * @return QueryBuilderInterface
     */
    public function getQueryBuilder();
    
    public function startTransaction();
    
    public function commit();
    
    public function rollback();
}
