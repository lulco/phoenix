<?php

namespace Phoenix\Database\Adapter;

use PDO;
use PDOStatement;
use Phoenix\Database\QueryBuilder\QueryBuilderInterface;
use Phoenix\Exception\DatabaseQueryExecuteException;

abstract class PdoAdapter implements AdapterInterface
{
    /** @var PDO */
    private $pdo;
    
    /** @var QueryBuilderInterface */
    protected $queryBuilder;
    
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->getQueryBuilder();
    }
    
    /**
     * @param string|PDOStatement $sql
     * @return PDOStatement|boolean
     * @throws DatabaseQueryExecuteException on error
     */
    public function execute($sql)
    {
        if ($sql instanceof PDOStatement) {
            $res = $sql->execute();
        } else {
            $res = $this->pdo->query($sql);
        }
        
        if ($res !== false) {
            return $res;
        }
        
        $this->throwError($sql);
    }

    /**
     * {@inheritdoc}
     */
    public function insert($table, array $data)
    {
        $statement = $this->buildInsertQuery($table, $data);
        if (!$statement) {
            $this->throwError($statement);
        }
        $res = $this->execute($statement);
        if ($res !== false) {
            return $this->pdo->lastInsertId();
        }
        $this->throwError($statement->queryString);
    }
    
    /**
     * {@inheritdoc}
     */
    public function buildInsertQuery($table, array $data)
    {
        $query = sprintf('INSERT INTO %s %s VALUES %s;', $this->queryBuilder->escapeString(addslashes($table)), $this->createKeys($data), $this->createValues($data));
        $statement = $this->pdo->prepare($query);
        foreach ($data as $key => $value) {
            $statement->bindValue($key, $value);
        }
        return $statement;
    }
    
    /**
     * {@inheritdoc}
     */
    public function update($table, array $data, array $conditions = [], $where = '')
    {
        $statement = $this->buildUpdateQuery($table, $data, $conditions, $where);
        if (!$statement) {
            $this->throwError($statement);
        }
        return $this->execute($statement);
    }
    
    /**
     * {@inheritdoc}
     */
    public function buildUpdateQuery($table, array $data, array $conditions = [], $where = '')
    {
        $values = [];
        foreach (array_keys($data) as $key) {
            $values[] = $this->queryBuilder->escapeString($key) . ' = ' . $this->createValue($key);
        }
        $query = sprintf('UPDATE %s SET %s%s;', $this->queryBuilder->escapeString(addslashes($table)), implode(', ', $values), $this->createWhere($conditions, $where));
        $statement = $this->pdo->prepare($query);
        foreach ($data as $key => $value) {
            $statement->bindValue($key, $value);
        }
        foreach ($conditions as $key => $condition) {
            $statement->bindValue('where_' . $key, $condition);
        }
        return $statement;
    }
    
    /**
     * {@inheritdoc}
     */
    public function delete($table, array $conditions = [], $where = '')
    {
        $statement = $this->buildDeleteQuery($table, $conditions, $where);
        if (!$statement) {
            $this->throwError($statement);
        }
        return $this->execute($statement);
    }
    
    /**
     * {@inheritdoc}
     */
    public function buildDeleteQuery($table, array $conditions = array(), $where = '')
    {
        $query = sprintf('DELETE FROM %s%s;', $this->queryBuilder->escapeString(addslashes($table)), $this->createWhere($conditions, $where));
        $statement = $this->pdo->prepare($query);
        foreach ($conditions as $key => $condition) {
            $statement->bindValue('where_' . $key, $condition);
        }
        return $statement;
    }
    
    /**
     * {@inheritdoc}
     */
    public function fetch($table, $fields = '*', array $conditions = array(), array $orders = array(), array $groups = array())
    {
        $query = $this->buildFetchQuery($table, $fields, $conditions, 1, $orders, $groups);
        return $this->execute($query)->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * {@inheritdoc}
     */
    public function fetchAll($table, $fields = '*', array $conditions = array(), $limit = null, array $orders = array(), array $groups = array())
    {
        $query = $this->buildFetchQuery($table, $fields, $conditions, $limit, $orders, $groups);
        return $this->execute($query)->fetchAll(PDO::FETCH_ASSOC);
    }

    private function buildFetchQuery($table, $fields, array $conditions = [], $limit = null, array $orders = [], array $groups = [])
    {
        $query = sprintf('SELECT %s FROM %s%s%s;', $fields, $this->queryBuilder->escapeString(addslashes($table)), $this->createWhere($conditions), $this->createLimit($limit));
        return $query;
    }
    
    private function createKeys($data)
    {
        $keys = [];
        foreach (array_keys($data) as $key) {
            $keys[] = $this->queryBuilder->escapeString($key);
        }
        return '(' . implode(', ', $keys) . ')';
    }
    
    private function createValues($data)
    {
        $values = [];
        foreach (array_keys($data) as $key) {
            $values[] = $this->createValue($key);
        }
        return '(' . implode(', ', $values) . ')';
    }
    
    private function createValue($key, $prefix = '')
    {
        return ':' . $prefix . $key;
    }
    
    private function createWhere(array $conditions = [], $where = '')
    {
        if (empty($conditions) && $where == '') {
            return '';
        }
        
        if (empty($conditions)) {
            return sprintf(' WHERE %s', $where);
        }
        $cond = [];
        foreach (array_keys($conditions) as $key) {
            $cond[] = $this->queryBuilder->escapeString($key) . ' = ' . $this->createValue($key, 'where_');
        }
        return sprintf(' WHERE %s', implode(' AND ', $cond) . ($where ? ' AND ' . $where : ''));
    }
    
    private function createLimit($limit = null)
    {
        if (!$limit) {
            return '';
        }
        return sprintf(' LIMIT %s', $limit);
    }
    
    /**
     * {@inheritdoc}
     */
    public function startTransaction()
    {
        return $this->pdo->beginTransaction();
    }
    
    /**
     * {@inheritdoc}
     */
    public function commit()
    {
        return $this->pdo->commit();
    }

    /**
     * {@inheritdoc}
     */
    public function rollback()
    {
        return $this->pdo->rollBack();
    }
    
    private function throwError($query)
    {
        $errorInfo = $this->pdo->errorInfo();
        throw new DatabaseQueryExecuteException('SQLSTATE[' . $errorInfo[0] . ']: ' . $errorInfo[2] . '. Query ' . print_R($query, true) . ' fails', $errorInfo[1]);
    }
}
