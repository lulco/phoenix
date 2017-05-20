<?php

namespace Phoenix\Database\Adapter;

use Phoenix\Database\Element\Column;
use Phoenix\Database\Element\Structure;
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
     * @param string $table
     * @param array $data
     */
    public function buildInsertQuery($table, array $data);

    /**
     * @param string $table
     * @param array $data
     * @param array $conditions
     * @param string $where
     */
    public function update($table, array $data, array $conditions = [], $where = '');

    /**
     * @param string $table
     * @param array $data
     * @param array $conditions
     * @param string $where
     */
    public function buildUpdateQuery($table, array $data, array $conditions = [], $where = '');

    /**
     * @param string $table
     * @param array $conditions
     * @param string $where
     */
    public function delete($table, array $conditions = [], $where = '');

    /**
     * @param string $sql
     * @return array
     */
    public function select($sql);

    /**
     * @param string $table
     * @param string $fields
     * @param array $conditions
     * @param array $orders
     * @param array $groups
     */
    public function fetch($table, $fields = '*', array $conditions = [], array $orders = [], array $groups = []);

    /**
     * @param string $table
     * @param string $fields
     * @param array $conditions
     * @param string|null $limit
     * @param array $orders
     * @param array $groups
     */
    public function fetchAll($table, $fields = '*', array $conditions = [], $limit = null, array $orders = [], array $groups = []);

    /**
     * @param string $table
     * @param array $conditions
     * @param string $where
     */
    public function buildDeleteQuery($table, array $conditions = [], $where = '');

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

    /**
     * @param string $table
     * @return Column[]
     */
    public function tableInfo($table);

    /**
     * @param string $charset
     * @return AdapterInterface
     */
    public function setCharset($charset);

    /**
     * @return string
     */
    public function getCharset();

    /**
     * @return Structure
     */
    public function getStructure();
}
