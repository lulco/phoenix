<?php

namespace Phoenix\Database\Adapter;

use Phoenix\Database\QueryBuilder\QueryBuilderInterface;

interface AdapterInterface
{
    /**
     * @param mixed $sql
     */
    public function execute($sql);
    
    /**
     * @param string $table
     * @param array $data
     * @return mixed last inserted id
     */
    public function insert($table, array $data);
    
    /**
     * @return QueryBuilderInterface
     */
    public function getQueryBuilder();
    
    /**
     * Initiates a transaction
     * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
     */
    public function startTransaction();
    
    /**
     * Commits a transaction
     * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
     */
    public function commit();
    
    /**
     * Rolls back a transaction
     * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
     */
    public function rollback();
}
