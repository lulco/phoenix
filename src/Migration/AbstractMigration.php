<?php

namespace Phoenix\Migration;

use PDOStatement;
use Phoenix\Database\Adapter\AdapterInterface;
use Phoenix\Database\Element\MigrationTable;
use Phoenix\Database\QueryBuilder\QueryBuilderInterface;
use Phoenix\Exception\DatabaseQueryExecuteException;
use ReflectionClass;

abstract class AbstractMigration
{
    /** @var AdapterInterface */
    private $adapter;

    /** @var string */
    private $datetime;

    /** @var string */
    private $className;

    /** @var string */
    private $fullClassName;

    /** @var array */
    private $queriesToExecute = [];

    /** @var array list of executed queries */
    private $executedQueries = [];

    public function __construct(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
        $classNameCreator = new ClassNameCreator((new ReflectionClass($this))->getFileName());
        $this->datetime = $classNameCreator->getDatetime();
        $this->className = $classNameCreator->getClassName();
        $this->fullClassName = $classNameCreator->getClassName();
    }

    final public function getDatetime(): string
    {
        return $this->datetime;
    }

    final public function getClassName(): string
    {
        return ltrim($this->className, '\\');
    }

    final public function getFullClassName(): string
    {
        return $this->fullClassName;
    }

    final public function migrate(bool $dry = false): array
    {
        $this->reset();
        $this->up();
        $queries = $this->prepare();
        return $this->runQueries($queries, $dry);
    }

    final public function rollback(bool $dry = false): array
    {
        $this->reset();
        $this->down();
        $queries = $this->prepare();
        return $this->runQueries($queries, $dry);
    }

    /**
     * @param string|PDOStatement $sql
     */
    final protected function execute($sql): void
    {
        $this->queriesToExecute[] = $sql;
    }

    /**
     * @param string $name
     * @param mixed $primaryKey available only for create table
     * true - if you want classic autoincrement integer primary column with name id
     * Column - if you want to define your own column (column is added to list of columns)
     * string - name of column in list of columns
     * array of strings - names of columns in list of columns
     * array of Column - list of own columns (all columns are added to list of columns)
     * other (false, null) - if your table doesn't have primary key
     * @return MigrationTable
     */
    final protected function table(string $name, $primaryKey = true, ?string $charset = null, ?string $collation = null): MigrationTable
    {
        $table = new MigrationTable($name, $primaryKey);
        $table->setCharset($charset ?: $this->adapter->getCharset());
        $table->setCollation($collation);

        $this->queriesToExecute[] = $table;
        return $table;
    }

    final protected function select(string $sql): array
    {
        return $this->adapter->select($sql);
    }

    final protected function fetch(string $table, string $fields = '*', array $conditions = [], array $orders = [], array $groups = []): array
    {
        return $this->adapter->fetch($table, $fields, $conditions, $orders, $groups);
    }

    final protected function fetchAll(string $table, string $fields = '*', array $conditions = [], ?string $limit = null, array $orders = [], array $groups = []): array
    {
        return $this->adapter->fetchAll($table, $fields, $conditions, $limit, $orders, $groups);
    }

    /**
     * adds insert query to list of queries to execute
     */
    final protected function insert(string $table, array $data): AbstractMigration
    {
        $this->execute($this->adapter->buildInsertQuery($table, $data));
        return $this;
    }

    /**
     * adds update query to list of queries to execute
     * @param string $table
     * @param array $data
     * @param array $conditions key => value conditions to generate WHERE part of query, imploded with AND
     * @param string $where additional where added to generated WHERE as is
     * @return AbstractMigration
     */
    final protected function update(string $table, array $data, array $conditions = [], string $where = ''): AbstractMigration
    {
        $this->execute($this->adapter->buildUpdateQuery($table, $data, $conditions, $where));
        return $this;
    }

    /**
     * adds delete query to list of queries to exectue
     * @param string $table
     * @param array $conditions key => value conditions to generate WHERE part of query, imploded with AND
     * @param string $where additional where added to generated WHERE as is
     * @return AbstractMigration
     */
    final protected function delete(string $table, array $conditions = [], string $where = ''): AbstractMigration
    {
        $this->execute($this->adapter->buildDeleteQuery($table, $conditions, $where));
        return $this;
    }

    /**
     * @throws DatabaseQueryExecuteException
     */
    private function runQueries(array $queries, bool $dry = false): array
    {
        $results = [];
        foreach ($queries as $query) {
            if (!$dry) {
                $result = $this->adapter->execute($query);
                $results[] = $result;
            }
            $this->executedQueries[] = $query instanceof PDOStatement ? $query->queryString : $query;
        }
        return $results;
    }

    public function getExecutedQueries(): array
    {
        return $this->executedQueries;
    }

    abstract protected function up()/*: void*/;

    abstract protected function down()/*: void*/;

    private function reset(): void
    {
        $this->executedQueries = [];
        $this->queriesToExecute = [];
    }

    private function prepare(): array
    {
        $queryBuilder = $this->adapter->getQueryBuilder();
        $queries = [];
        foreach ($this->queriesToExecute as $queryToExecute) {
            if (!($queryToExecute instanceof MigrationTable)) {
                $queries[] = $queryToExecute;
                continue;
            }
            $tableQueries = $this->prepareMigrationTableQueries($queryToExecute, $queryBuilder);
            $queries = array_merge($queries, $tableQueries);
        }
        return $queries;
    }

    private function prepareMigrationTableQueries(MigrationTable $table, QueryBuilderInterface $queryBuilder): array
    {
        $tableQueries = [];
        if ($table->getAction() === MigrationTable::ACTION_CREATE) {
            $tableQueries = $queryBuilder->createTable($table);
        } elseif ($table->getAction() === MigrationTable::ACTION_ALTER) {
            $tableQueries = $queryBuilder->alterTable($table);
        } elseif ($table->getAction() === MigrationTable::ACTION_RENAME) {
            $tableQueries = $queryBuilder->renameTable($table);
        } elseif ($table->getAction() === MigrationTable::ACTION_DROP) {
            $tableQueries = $queryBuilder->dropTable($table);
        } elseif ($table->getAction() === MigrationTable::ACTION_COPY) {
            $tableQueries = $queryBuilder->copyTable($table);
        }
        return $tableQueries;
    }
}
