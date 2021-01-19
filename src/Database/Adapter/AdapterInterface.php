<?php

namespace Phoenix\Database\Adapter;

use PDOStatement;
use Phoenix\Database\Element\Structure;
use Phoenix\Database\QueryBuilder\QueryBuilderInterface;
use Phoenix\Exception\DatabaseQueryExecuteException;

interface AdapterInterface
{
    /**
     * @param PDOStatement $sql
     * @return bool
     * @throws DatabaseQueryExecuteException on error
     */
    public function execute(PDOStatement $sql): bool;

    /**
     * @param string $sql
     * @return PDOStatement
     * @throws DatabaseQueryExecuteException on error
     */
    public function query(string $sql): PDOStatement;

    /**
     * @param string $table
     * @param mixed[] $data
     * @return mixed last inserted id
     */
    public function insert(string $table, array $data);

    /**
     * @param string $table
     * @param mixed[] $data
     * @return PDOStatement
     */
    public function buildInsertQuery(string $table, array $data): PDOStatement;

    /**
     * @param string $table
     * @param array<string, mixed> $data
     * @param array<string, mixed> $conditions
     * @param string $where
     * @return bool
     */
    public function update(string $table, array $data, array $conditions = [], string $where = ''): bool;

    /**
     * @param string $table
     * @param array<string, mixed> $data
     * @param array<string, mixed> $conditions
     * @param string $where
     * @return PDOStatement
     */
    public function buildUpdateQuery(string $table, array $data, array $conditions = [], string $where = ''): PDOStatement;

    /**
     * @param string $table
     * @param array<string, mixed> $conditions
     * @param string $where
     * @return bool
     */
    public function delete(string $table, array $conditions = [], string $where = ''): bool;

    /**
     * @param string $table
     * @param array<string, mixed> $conditions
     * @param string $where
     * @return PDOStatement
     */
    public function buildDeleteQuery(string $table, array $conditions = [], string $where = ''): PDOStatement;

    public function buildDoNotCheckForeignKeysQuery(): string;

    public function buildCheckForeignKeysQuery(): string;

    /**
     * @param string $sql
     * @return array<array<string, mixed>>
     */
    public function select(string $sql): array;

    /**
     * @param string $table
     * @param string[] $fields
     * @param array<string, mixed> $conditions
     * @param string[]|array<string, string> $orders
     * @param string[] $groups
     * @return array<string, mixed>|null
     */
    public function fetch(string $table, array $fields = ['*'], array $conditions = [], array $orders = [], array $groups = []): ?array;

    /**
     * @param string $table
     * @param string[] $fields
     * @param array<string, mixed> $conditions
     * @param string|null $limit
     * @param string[]|array<string, string> $orders
     * @param string[] $groups
     * @return array<array<string, mixed>>
     */
    public function fetchAll(string $table, array $fields = ['*'], array $conditions = [], ?string $limit = null, array $orders = [], array $groups = []): array;

    /**
     * @return QueryBuilderInterface
     */
    public function getQueryBuilder();

    /**
     * Initiates a transaction
     * @return bool
     */
    public function startTransaction(): bool;

    /**
     * Commits a transaction
     * @return bool
     */
    public function commit(): bool;

    /**
     * Rolls back a transaction
     * @return bool
     */
    public function rollback(): bool;

    public function setCharset(string $charset): AdapterInterface;

    public function getCharset(): ?string;

    public function getStructure(): Structure;
}
