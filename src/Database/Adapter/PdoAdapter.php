<?php

declare(strict_types=1);

namespace Phoenix\Database\Adapter;

use DateTime;
use InvalidArgumentException;
use UnexpectedValueException;
use PDO;
use PDOStatement;
use Phoenix\Database\Adapter\Behavior\StructureBehavior;
use Phoenix\Exception\DatabaseQueryExecuteException;

abstract class PdoAdapter implements AdapterInterface
{
    use StructureBehavior;

    private PDO $pdo;

    private ?string $charset = null;

    private ?string $collation = null;

    protected ?string $version;

    public function __construct(PDO $pdo, ?string $version = null)
    {
        $this->pdo = $pdo;
        $this->version = $version;
    }

    /**
     * @param PDOStatement $sql
     * @return bool
     * @throws DatabaseQueryExecuteException on error
     */
    public function execute(PDOStatement $sql): bool
    {
        $res = $sql->execute();
        if ($res === false) {
            $this->throwError($sql);
        }
        return $res;
    }

    /**
     * @param string $sql
     * @return PDOStatement
     * @throws DatabaseQueryExecuteException on error
     */
    public function query(string $sql): PDOStatement
    {
        $res = $this->pdo->query($sql);
        if (!$res instanceof PDOStatement) {
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
        $this->bindConditions($statement, $conditions);
        return $statement;
    }

    public function select(string $sql): array
    {
        if (strpos(strtoupper($sql), 'SELECT ') === 0) {
            /** @var array<mixed[]> $result */
            $result = $this->query($sql)->fetchAll(PDO::FETCH_ASSOC);
            return $result;
        }
        throw new InvalidArgumentException('Only select query can be executed in select method');
    }

    public function fetch(string $table, array $fields = ['*'], array $conditions = [], array $orders = [], array $groups = []): ?array
    {
        $statement = $this->buildFetchQuery($table, $fields, $conditions, '1', $orders, $groups);
        $this->execute($statement);
        $res = $statement->fetch(PDO::FETCH_ASSOC);
        return $res ?: null;
    }

    public function fetchAll(string $table, array $fields = ['*'], array $conditions = [], ?string $limit = null, array $orders = [], array $groups = []): array
    {
        $statement = $this->buildFetchQuery($table, $fields, $conditions, $limit, $orders, $groups);
        $this->execute($statement);
        /** @var array<mixed[]> $result */
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }

    /**
     * @param string $table
     * @param string[] $fields
     * @param array<string, mixed> $conditions
     * @param string|null $limit
     * @param string[]|array<string, string> $orders
     * @param string[] $groups
     * @return PDOStatement
     */
    private function buildFetchQuery(string $table, array $fields = ['*'], array $conditions = [], ?string $limit = null, array $orders = [], array $groups = []): PDOStatement
    {
        $query = sprintf('SELECT %s FROM %s%s%s%s%s;', implode(', ', $fields), $this->escapeString(addslashes($table)), $this->createWhere($conditions), $this->createGroup($groups), $this->createOrder($orders), $this->createLimit($limit));
        $statement = $this->pdo->prepare($query);
        $this->bindConditions($statement, $conditions);
        return $statement;
    }

    /**
     * @param mixed[]|array<mixed[]> $data
     * @return string
     */
    private function createKeys(array $data): string
    {
        $keys = [];
        if ($this->isMulti($data)) {
            $data = current($data);
        }
        /** @var string $key */
        foreach (array_keys($data) as $key) {
            $keys[] = $this->escapeString($key);
        }
        return '(' . implode(', ', $keys) . ')';
    }

    /**
     * @param mixed[]|array<mixed[]> $data
     * @return string
     */
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

    /**
     * @param array<string, mixed> $data
     * @param string $prefix
     * @return string
     */
    private function createValueString(array $data, string $prefix = ''): string
    {
        $values = [];
        foreach (array_keys($data) as $key) {
            $values[] = $this->createValue($key, $prefix);
        }
        return '(' . implode(', ', $values) . ')';
    }

    /**
     * @param array<string, mixed> $conditions
     * @param string $where
     * @return string
     */
    private function createWhere(array $conditions = [], string $where = ''): string
    {
        if (empty($conditions) && $where === '') {
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

    /**
     * @param string $key
     * @param mixed $value
     * @return string
     */
    private function addCondition(string $key, $value): string
    {
        $columnNameAndOperator = $this->splitColumnNameAndOperator($key, $value);
        $columnName = $columnNameAndOperator['columnName'];
        $operator = $columnNameAndOperator['operator'];
        if (is_array($value)) {
            $inConditions = [];
            foreach (array_keys($value) as $index) {
                $inConditions[] = $this->createValue($columnName, 'where_' . $index . '_');
            }
            $rightOperand = '(' . implode(', ', $inConditions) . ')';
        } else {
            $rightOperand = $this->createValue($columnName, 'where_');
        }
        return $this->escapeString($columnName) . ' ' . $operator . ' ' . $rightOperand;
    }

    private function createLimit(?string $limit = null): string
    {
        if (!$limit) {
            return '';
        }
        return sprintf(' LIMIT %s', $limit);
    }

    /**
     * @param string[]|array<string, string> $orders
     * @return string
     */
    private function createOrder(array $orders = []): string
    {
        if (empty($orders)) {
            return '';
        }
        $listOfOrders = [];
        foreach ($orders as $column => $way) {
            if (!in_array(strtoupper($way), ['ASC', 'DESC'], true)) {
                $column = $way;
                $way = 'ASC';
            }
            $way = strtoupper($way);
            $listOfOrders[] = $column . ' ' . $way;
        }
        return sprintf(' ORDER BY %s', implode(', ', $listOfOrders));
    }

    /**
     * @param string[] $groups
     * @return string
     */
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
        if (!$this->pdo->inTransaction()) {
            return true;    // back compatibility for PHP 8.0 - autocommited transaction throws error here
        }
        return $this->pdo->commit();
    }

    /**
     * {@inheritdoc}
     */
    public function rollback(): bool
    {
        if (!$this->pdo->inTransaction()) {
            return true;    // back compatibility for PHP 8.0 - autocommited transaction throws error here
        }
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

    public function setCollation(?string $collation): AdapterInterface
    {
        $this->collation = $collation;
        return $this;
    }

    public function getCollation(): ?string
    {
        return $this->collation;
    }

    /**
     * @param string|null $lengthAndDecimals
     * @return array<int|null>
     */
    protected function getLengthAndDecimals(?string $lengthAndDecimals = null): array
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

    /**
     * @param PDOStatement $statement
     * @param array<string, mixed> $data
     * @param string $prefix
     */
    private function bindDataValues(PDOStatement $statement, array $data, string $prefix = ''): void
    {
        foreach ($data as $key => $value) {
            if ($value instanceof DateTime) {
                $value = $value->format('Y-m-d H:i:s');
            }
            $statement->bindValue($prefix . $key, $this->createRealValue($value));
        }
    }

    /**
     * @param PDOStatement $statement
     * @param array<string, mixed> $conditions
     */
    private function bindConditions(PDOStatement $statement, array $conditions = []): void
    {
        foreach ($conditions as $key => $condition) {
            $this->bindCondition($statement, $key, $condition);
        }
    }

    /**
     * @param PDOStatement $statement
     * @param string $key
     * @param mixed $condition
     */
    private function bindCondition(PDOStatement $statement, string $key, $condition): void
    {
        $columnNameAndOperator = $this->splitColumnNameAndOperator($key, $condition);
        $columnName = $columnNameAndOperator['columnName'];
        if (!is_array($condition)) {
            $statement->bindValue('where_' . $columnName, $condition);
            return;
        }
        foreach ($condition as $index => $cond) {
            $statement->bindValue('where_' . $index . '_' . $columnName, $cond);
        }
    }

    /**
     * @param array<mixed> $data
     * @return bool
     */
    private function isMulti(array $data): bool
    {
        foreach ($data as $item) {
            if (!is_array($item)) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return array{'columnName': string, 'operator': string}
     */
    private function splitColumnNameAndOperator(string $key, $value): array
    {
        // initialize both column name and operator
        $columnName = $key;
        $operator = '=';

        // check presence of operator in $key
        if (preg_match('/^(.*) (=|!=|<>|<|<=|>|>=)$/', $key, $matches) === 1) {
            $columnName = $matches[1];
            $operator = $matches[2];
        }

        $columnName = trim($columnName);
        if ($value === null) {
            if ($operator === '=') {
                return [
                    'columnName' => $columnName,
                    'operator' => 'IS',
                ];
            }
            if (in_array($operator, ['!=', '<>'])) {
                return [
                    'columnName' => $columnName,
                    'operator' => 'IS NOT',
                ];
            }
            throw new UnexpectedValueException('Cannot accept "' . $operator . '" operator for NULL value');
        }

        if (is_array($value)) {
            if ($operator === '=') {
                return [
                    'columnName' => $columnName,
                    'operator' => 'IN',
                ];
            }
            if (in_array($operator, ['!=', '<>'])) {
                return [
                    'columnName' => $columnName,
                    'operator' => 'NOT IN',
                ];
            }
            throw new UnexpectedValueException('Cannot accept "' . $operator . '" operator for list value');
        }

        // always prefer '<>' to '!='
        if ($operator === '!=') {
            $operator = '<>';
        }
        return [
            'columnName' => $columnName,
            'operator' => $operator
        ];
    }

    /**
     * @param string|PDOStatement $query
     * @throws DatabaseQueryExecuteException
     */
    private function throwError($query): void
    {
        $errorInfo = $this->pdo->errorInfo();
        throw new DatabaseQueryExecuteException(
            sprintf(
                'SQLSTATE[%s]: %s.%s',
                $errorInfo[0] ?? '',
                $errorInfo[2] ?? '',
                ($query ? ' Query ' . print_r($query, true) . ' fails' : '')
            ),
            $errorInfo[1] ?? 0
        );
    }

    abstract protected function escapeString(string $string): string;

    /**
     * @param mixed $value
     * @return mixed
     */
    abstract protected function createRealValue($value);
}
