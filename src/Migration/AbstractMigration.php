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

    /**
     * @param AdapterInterface $adapter
     */
    public function __construct(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
        $classNameCreator = new ClassNameCreator((new ReflectionClass($this))->getFileName());
        $this->datetime = $classNameCreator->getDatetime();
        $this->className = $classNameCreator->getClassName();
        $this->fullClassName = $classNameCreator->getClassName();
    }

    /**
     * @return string
     */
    final public function getDatetime()
    {
        return $this->datetime;
    }

    /**
     * @return string
     */
    final public function getClassName()
    {
        return ltrim($this->className, '\\');
    }

    /**
     * @return string
     */
    final public function getFullClassName()
    {
        return $this->fullClassName;
    }

    /**
     * @param boolean $dry Only create query strings, do not execute
     * @return array
     */
    final public function migrate($dry = false)
    {
        $this->reset();
        $this->up();
        $queries = $this->prepare();
        return $this->runQueries($queries, $dry);
    }

    /**
     * @param boolean $dry Only create query strings, do not execute
     * @return array
     */
    final public function rollback($dry = false)
    {
        $this->reset();
        $this->down();
        $queries = $this->prepare();
        return $this->runQueries($queries, $dry);
    }

    /**
     * adds sql to list of queries to execute
     * @param string $sql
     */
    final protected function execute($sql)
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
    final protected function table($name, $primaryKey = true, $charset = null, $collation = null)
    {
        $table = new MigrationTable($name, $primaryKey);
        $table->setCharset($charset ?: $this->adapter->getCharset());
        $table->setCollation($collation);

        $this->queriesToExecute[] = $table;
        return $table;
    }

    /**
     * execute SELECT query and returns result
     * @param string $sql
     * @return array
     */
    final protected function select($sql)
    {
        return $this->adapter->select($sql);
    }

    /**
     * @param string $table
     * @param string $fields
     * @param array $conditions
     * @param array $orders
     * @param array $groups
     * @return array
     */
    final protected function fetch($table, $fields = '*', array $conditions = [], array $orders = [], array $groups = [])
    {
        return $this->adapter->fetch($table, $fields, $conditions, $orders, $groups);
    }

    /**
     * @param string $table
     * @param string $fields
     * @param array $conditions
     * @param int|null $limit
     * @param array $orders
     * @param array $groups
     * @return array
     */
    final protected function fetchAll($table, $fields = '*', array $conditions = [], $limit = null, array $orders = [], array $groups = [])
    {
        return $this->adapter->fetchAll($table, $fields, $conditions, $limit, $orders, $groups);
    }

    /**
     * adds insert query to list of queries to execute
     * @param string $table
     * @param array $data
     * @return AbstractMigration
     */
    final protected function insert($table, array $data)
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
    final protected function update($table, array $data, array $conditions = [], $where = '')
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
    final protected function delete($table, array $conditions = [], $where = '')
    {
        $this->execute($this->adapter->buildDeleteQuery($table, $conditions, $where));
        return $this;
    }

    /**
     *
     * @param boolean $dry do not execute query
     * @return array
     * @throws DatabaseQueryExecuteException
     */
    private function runQueries(array $queries, $dry = false)
    {
        $results = [];
        try {
            foreach ($queries as $query) {
                if (!$dry) {
                    $result = $this->adapter->execute($query);
                    $results[] = $result;
                }
                $this->executedQueries[] = $query instanceof PDOStatement ? $query->queryString : $query;
            }
        } catch (DatabaseQueryExecuteException $e) {
            throw $e;
        }
        return $results;
    }

    /**
     * @return array
     */
    public function getExecutedQueries()
    {
        return $this->executedQueries;
    }

    /**
     * need override
     */
    abstract protected function up();

    /**
     * need override
     */
    abstract protected function down();

    private function reset()
    {
        $this->executedQueries = [];
        $this->queriesToExecute = [];
    }

    private function prepare()
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

    private function prepareMigrationTableQueries(MigrationTable $table, QueryBuilderInterface $queryBuilder)
    {
        $tableQueries = null;
        if ($table->getAction() === MigrationTable::ACTION_CREATE) {
            $tableQueries = $queryBuilder->createTable($table);
        } elseif ($table->getAction() === MigrationTable::ACTION_ALTER) {
            $tableQueries = $queryBuilder->alterTable($table);
        } elseif ($table->getAction() === MigrationTable::ACTION_RENAME) {
            $tableQueries = $queryBuilder->renameTable($table, $table->getNewName());
        } elseif ($table->getAction() === MigrationTable::ACTION_DROP) {
            $tableQueries = $queryBuilder->dropTable($table);
        }
        return $tableQueries;
    }
}
