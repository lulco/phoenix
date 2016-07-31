<?php

namespace Phoenix\Database\Adapter;

use DateTime;
use InvalidArgumentException;
use PDO;
use PDOStatement;
use Phoenix\Database\QueryBuilder\QueryBuilderInterface;
use Phoenix\Exception\DatabaseQueryExecuteException;

abstract class PdoAdapter implements AdapterInterface
{
    /** @var PDO */
    private $pdo;
    
    private $charset;
    
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

        if ($res === false) {
            $this->throwError($sql);
        }
        return $res;
    }

    /**
     * {@inheritdoc}
     */
    public function insert($table, array $data)
    {
        $statement = $this->buildInsertQuery($table, $data);
        $this->execute($statement);
        return $this->pdo->lastInsertId();
    }
    
    /**
     * {@inheritdoc}
     */
    public function buildInsertQuery($table, array $data)
    {
        $query = sprintf('INSERT INTO %s %s VALUES %s;', $this->queryBuilder->escapeString(addslashes($table)), $this->createKeys($data), $this->createValues($data));
        $statement = $this->pdo->prepare($query);
        if (!$statement) {
            $this->throwError($statement);
        }
        if (!$this->isMulti($data)) {
            $this->bindDataValues($statement, $data);
            return $statement;
        }
        foreach ($data as $index => $item) {
            $this->bindDataValues($statement, $item, 'item_' . $index . '_');
        }
        return $statement;
    }
    
    /**
     * {@inheritdoc}
     */
    public function update($table, array $data, array $conditions = [], $where = '')
    {
        $statement = $this->buildUpdateQuery($table, $data, $conditions, $where);
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
        if (!$statement) {
            $this->throwError($statement);
        }
        foreach ($data as $key => $value) {
            if ($value instanceof DateTime) {
                $value = $value->format('Y-m-d H:i:s');
            }
            $statement->bindValue($key, $value);
        }
        
        $this->bindConditions($statement, $conditions);
        return $statement;
    }
    
    /**
     * {@inheritdoc}
     */
    public function delete($table, array $conditions = [], $where = '')
    {
        $statement = $this->buildDeleteQuery($table, $conditions, $where);
        return $this->execute($statement);
    }
    
    /**
     * {@inheritdoc}
     */
    public function buildDeleteQuery($table, array $conditions = [], $where = '')
    {
        $query = sprintf('DELETE FROM %s%s;', $this->queryBuilder->escapeString(addslashes($table)), $this->createWhere($conditions, $where));
        $statement = $this->pdo->prepare($query);
        if (!$statement) {
            $this->throwError($statement);
        }
        $this->bindConditions($statement, $conditions);
        return $statement;
    }
    
    /**
     * {@inheritdoc}
     */
    public function select($sql)
    {
        if (strpos($sql, 'SELECT ') === 0) {
            return $this->execute($sql)->fetchAll(PDO::FETCH_ASSOC);
        }
        throw new InvalidArgumentException('Only select query can be executed in select method');
    }

    /**
     * {@inheritdoc}
     */
    public function fetch($table, $fields = '*', array $conditions = [], array $orders = [], array $groups = [])
    {
        $statement = $this->buildFetchQuery($table, $fields, $conditions, 1, $orders, $groups);
        $this->execute($statement);
        return $statement->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * {@inheritdoc}
     */
    public function fetchAll($table, $fields = '*', array $conditions = [], $limit = null, array $orders = [], array $groups = [])
    {
        $statement = $this->buildFetchQuery($table, $fields, $conditions, $limit, $orders, $groups);
        $this->execute($statement);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    private function buildFetchQuery($table, $fields, array $conditions = [], $limit = null, array $orders = [], array $groups = [])
    {
        $query = sprintf('SELECT %s FROM %s%s%s%s%s;', $fields, $this->queryBuilder->escapeString(addslashes($table)), $this->createWhere($conditions), $this->createGroup($groups), $this->createOrder($orders), $this->createLimit($limit));
        $statement = $this->pdo->prepare($query);
        if (!$statement) {
            $this->throwError($statement);
        }
        $this->bindConditions($statement, $conditions);
        return $statement;
    }

    private function createKeys($data)
    {
        $keys = [];
        if ($this->isMulti($data)) {
            $data = current($data);
        }
        foreach (array_keys($data) as $key) {
            $keys[] = $this->queryBuilder->escapeString($key);
        }
        return '(' . implode(', ', $keys) . ')';
    }

    private function createValues($data)
    {
        if (!$this->isMulti($data)) {
            return $this->createValueString($data);
        }
        $values = [];
        foreach ($data as $index => $item) {
            $values[] = $this->createValueString($item, 'item_' . $index . '_');
        }
        return implode(', ', $values);
    }

    private function createValue($key, $prefix = '')
    {
        return ':' . $prefix . $key;
    }

    private function createValueString($data, $prefix = '')
    {
        $values = [];
        foreach (array_keys($data) as $key) {
            $values[] = $this->createValue($key, $prefix);
        }
        return '(' . implode(', ', $values) . ')';
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
        foreach ($conditions as $key => $value) {
            if (is_array($value)) {
                $in = [];
                foreach ($value as $index => $val) {
                    $in[] = $this->createValue($key, 'where_' . $index . '_');
                }
                $cond[] = $this->queryBuilder->escapeString($key) . ' IN (' . implode(', ', $in) . ')';
            } else {
                $cond[] = $this->queryBuilder->escapeString($key) . ' = ' . $this->createValue($key, 'where_');
            }
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

    private function createOrder(array $orders = [])
    {
        if (empty($orders)) {
            return '';
        }
        $listOfOrders = [];
        foreach ($orders as $column => $way) {
            if (!in_array(strtoupper($way), ['ASC', 'DESC'])) {
                $column = $way;
                $way = 'ASC';
            }
            $way = strtoupper($way);
            $listOfOrders[] = $column . ' ' . $way;
        }
        return sprintf(' ORDER BY %s', implode(', ', $listOfOrders));
    }

    private function createGroup(array $groups = [])
    {
        if (empty($groups)) {
            return '';
        }
        return sprintf(' GROUP BY %s', implode(', ', $groups));
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
    
    /**
     * {@inheritdoc}
     */
    public function setCharset($charset)
    {
        $this->charset = $charset;
        return $this;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getCharset()
    {
        return $this->charset;
    }
   
    /**
     * {@inheritdoc}
     */
    abstract public function tableInfo($table);

    private function bindDataValues($statement, $data, $prefix = '')
    {
        foreach ($data as $key => $value) {
            if ($value instanceof DateTime) {
                $value = $value->format('Y-m-d H:i:s');
            }
            $statement->bindValue($prefix . $key, $value);
        }
    }
    
    private function bindConditions(PDOStatement $statement, array $conditions = [])
    {        
        foreach ($conditions as $key => $condition) {
            if (!is_array($condition)) {
                $statement->bindValue('where_' . $key, $condition);
            } else {
                foreach ($condition as $index => $cond) {
                    $statement->bindValue('where_' . $index . '_' . $key, $cond);
                }
            }
        }
    }

    private function isMulti($data)
    {
        foreach ($data as $item) {
            if (is_array($item)) {
                return true;
            }
        }
        return false;
    }
        
    private function throwError($query)
    {
        $errorInfo = $this->pdo->errorInfo();
        throw new DatabaseQueryExecuteException('SQLSTATE[' . $errorInfo[0] . ']: ' . $errorInfo[2] . '.' . ($query ? ' Query ' . print_R($query, true) . ' fails' : ''), $errorInfo[1]);
    }
}
