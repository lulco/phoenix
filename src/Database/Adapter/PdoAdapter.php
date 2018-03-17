<?php

namespace Phoenix\Database\Adapter;

use DateTime;
use InvalidArgumentException;
use PDO;
use PDOStatement;
use Phoenix\Database\Adapter\Behavior\StructureBehavior;
use Phoenix\Exception\DatabaseQueryExecuteException;

abstract class PdoAdapter implements AdapterInterface
{
    use StructureBehavior;

    /** @var PDO */
    private $pdo;

    private $charset;

    protected $queryBuilder;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * @param string|PDOStatement $sql
     * @return PDOStatement|bool
     * @throws DatabaseQueryExecuteException on error
     */
    public function execute($sql)
    {
        $res = $sql instanceof PDOStatement ? $sql->execute() : $this->pdo->query($sql);
        if ($res === false) {
            $this->throwError($sql);
        }
        return $res;
    }

    /**
     * {@inheritdoc}
     */
    public function insert(string $table, array $data)
    {
        $statement = $this->buildInsertQuery($table, $data);
        $this->execute($statement);
        return $this->pdo->lastInsertId();
    }

    public function buildInsertQuery(string $table, array $data): PDOStatement
    {
        $query = sprintf('INSERT INTO %s %s VALUES %s;', $this->escapeString(addslashes($table)), $this->createKeys($data), $this->createValues($data));
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

    public function update(string $table, array $data, array $conditions = [], string $where = ''): bool
    {
        $statement = $this->buildUpdateQuery($table, $data, $conditions, $where);
        return $this->execute($statement);
    }

    public function buildUpdateQuery(string $table, array $data, array $conditions = [], string $where = ''): PDOStatement
    {
        $values = [];
        foreach (array_keys($data) as $key) {
            $values[] = $this->escapeString($key) . ' = ' . $this->createValue($key);
        }
        $query = sprintf('UPDATE %s SET %s%s;', $this->escapeString(addslashes($table)), implode(', ', $values), $this->createWhere($conditions, $where));
        $statement = $this->pdo->prepare($query);
        if (!$statement) {
            $this->throwError($statement);
        }
        $this->bindDataValues($statement, $data);
        $this->bindConditions($statement, $conditions);
        return $statement;
    }

    public function delete(string $table, array $conditions = [], string $where = ''): bool
    {
        $statement = $this->buildDeleteQuery($table, $conditions, $where);
        return $this->execute($statement);
    }

    public function buildDeleteQuery(string $table, array $conditions = [], string $where = ''): PDOStatement
    {
        $query = sprintf('DELETE FROM %s%s;', $this->escapeString(addslashes($table)), $this->createWhere($conditions, $where));
        $statement = $this->pdo->prepare($query);
        if (!$statement) {
            $this->throwError($statement);
        }
        $this->bindConditions($statement, $conditions);
        return $statement;
    }

    public function select(string $sql): array
    {
        if (strpos(strtoupper($sql), 'SELECT ') === 0) {
            return $this->execute($sql)->fetchAll(PDO::FETCH_ASSOC);
        }
        throw new InvalidArgumentException('Only select query can be executed in select method');
    }

    public function fetch(string $table, array $fields = ['*'], array $conditions = [], array $orders = [], array $groups = []): array
    {
        $statement = $this->buildFetchQuery($table, $fields, $conditions, '1', $orders, $groups);
        $this->execute($statement);
        return $statement->fetch(PDO::FETCH_ASSOC);
    }

    public function fetchAll(string $table, array $fields = ['*'], array $conditions = [], ?string $limit = null, array $orders = [], array $groups = []): array
    {
        $statement = $this->buildFetchQuery($table, $fields, $conditions, $limit, $orders, $groups);
        $this->execute($statement);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    private function buildFetchQuery(string $table, array $fields = ['*'], array $conditions = [], ?string $limit = null, array $orders = [], array $groups = []): PDOStatement
    {
        $query = sprintf('SELECT %s FROM %s%s%s%s%s;', implode(', ', $fields), $this->escapeString(addslashes($table)), $this->createWhere($conditions), $this->createGroup($groups), $this->createOrder($orders), $this->createLimit($limit));
        $statement = $this->pdo->prepare($query);
        if (!$statement) {
            $this->throwError($statement);
        }
        $this->bindConditions($statement, $conditions);
        return $statement;
    }

    private function createKeys(array $data): string
    {
        $keys = [];
        if ($this->isMulti($data)) {
            $data = current($data);
        }
        foreach (array_keys($data) as $key) {
            $keys[] = $this->escapeString($key);
        }
        return '(' . implode(', ', $keys) . ')';
    }

    private function createValues(array $data): string
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

    private function createValue(string $key, string $prefix = ''): string
    {
        return ':' . $prefix . $key;
    }

    private function createValueString(array $data, string $prefix = ''): string
    {
        $values = [];
        foreach (array_keys($data) as $key) {
            $values[] = $this->createValue($key, $prefix);
        }
        return '(' . implode(', ', $values) . ')';
    }

    private function createWhere(array $conditions = [], string $where = ''): string
    {
        if (empty($conditions) && $where == '') {
            return '';
        }

        if (empty($conditions)) {
            return sprintf(' WHERE %s', $where);
        }
        $cond = [];
        foreach ($conditions as $key => $value) {
            $cond[] = $this->addCondition($key, $value);
        }
        return sprintf(' WHERE %s', implode(' AND ', $cond) . ($where ? ' AND ' . $where : ''));
    }

    private function addCondition(string $key, $value): string
    {
        if (!is_array($value)) {
            return $this->escapeString($key) . ' = ' . $this->createValue($key, 'where_');
        }
        $inConditions = [];
        foreach (array_keys($value) as $index) {
            $inConditions[] = $this->createValue($key, 'where_' . $index . '_');
        }
        return $this->escapeString($key) . ' IN (' . implode(', ', $inConditions) . ')';
    }

    private function createLimit(?string $limit = null): string
    {
        if (!$limit) {
            return '';
        }
        return sprintf(' LIMIT %s', $limit);
    }

    private function createOrder(array $orders = []): string
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

    private function createGroup(array $groups = []): string
    {
        if (empty($groups)) {
            return '';
        }
        return sprintf(' GROUP BY %s', implode(', ', $groups));
    }

    /**
     * {@inheritdoc}
     */
    public function startTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * {@inheritdoc}
     */
    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    /**
     * {@inheritdoc}
     */
    public function rollback(): bool
    {
        return $this->pdo->rollBack();
    }

    public function setCharset(string $charset): AdapterInterface
    {
        $this->charset = $charset;
        return $this;
    }

    public function getCharset(): ?string
    {
        return $this->charset;
    }

    protected function getLengthAndDecimals(?string $lengthAndDecimals = null)
    {
        if ($lengthAndDecimals === null) {
            return [null, null];
        }

        $length = (int) $lengthAndDecimals;
        $decimals = null;
        if (strpos($lengthAndDecimals, ',')) {
            list($length, $decimals) = array_map('intval', explode(',', $lengthAndDecimals, 2));
        }
        return [$length, $decimals];
    }

    private function bindDataValues(PDOStatement $statement, array $data, string $prefix = '')
    {
        foreach ($data as $key => $value) {
            if ($value instanceof DateTime) {
                $value = $value->format('Y-m-d H:i:s');
            }
            $statement->bindValue($prefix . $key, $this->createRealValue($value));
        }
    }

    private function bindConditions(PDOStatement $statement, array $conditions = [])
    {
        foreach ($conditions as $key => $condition) {
            $this->bindCondition($statement, $key, $condition);
        }
    }

    private function bindCondition(PDOStatement $statement, string $key, $condition)
    {
        if (!is_array($condition)) {
            $statement->bindValue('where_' . $key, $condition);
            return;
        }
        foreach ($condition as $index => $cond) {
            $statement->bindValue('where_' . $index . '_' . $key, $cond);
        }
    }

    private function isMulti(array $data): bool
    {
        foreach ($data as $item) {
            if (!is_array($item)) {
                return false;
            }
        }
        return true;
    }

    private function throwError($query)
    {
        $errorInfo = $this->pdo->errorInfo();
        throw new DatabaseQueryExecuteException('SQLSTATE[' . $errorInfo[0] . ']: ' . $errorInfo[2] . '.' . ($query ? ' Query ' . print_r($query, true) . ' fails' : ''), $errorInfo[1]);
    }

    abstract protected function escapeString(string $string): string;

    abstract protected function createRealValue($value): ?string;
}
