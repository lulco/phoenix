<?php

namespace Phoenix\Migration;

use InvalidArgumentException;
use PDOStatement;
use Phoenix\Database\Adapter\AdapterInterface;
use Phoenix\Database\Element\Column;
use Phoenix\Database\Element\ForeignKey;
use Phoenix\Database\Element\Index;
use Phoenix\Database\Element\Table;
use Phoenix\Exception\DatabaseQueryExecuteException;
use Phoenix\Exception\IncorrectMethodUsageException;
use ReflectionClass;
use RuntimeException;

abstract class AbstractMigration
{
    /** @var string */
    private $datetime;

    /** @var string */
    private $className;

    /** @var string */
    private $fullClassName;

    private $something = [];    // TODO vymysliet nazov

    /** @var Table */
    private $table = null;

    private $primaryKey = null;

    /** @var array */
    private $tables = [];

    /** @var array list of queries to run */
    private $queries = [];

    /** @var array list of executed queries */
    private $executedQueries = [];

    /** @var AdapterInterface */
    private $adapter;

    /** @var boolean wrap queries in up / down to transaction */
    protected $useTransaction = false;  // not working for now :( so I turn it off

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
        $this->up();
        $this->prepare();
        return $this->runQueries($dry);
    }

    /**
     * @param boolean $dry Only create query strings, do not execute
     * @return array
     */
    final public function rollback($dry = false)
    {
        $this->down();
        $this->prepare();
        return $this->runQueries($dry);
    }

    /**
     * need override
     */
    abstract protected function up();

    /**
     * need override
     */
    abstract protected function down();

    private function prepare()
    {
        $queryBuilder = $this->adapter->getQueryBuilder();
        foreach ($this->something as $something) {
            $queries = null;
            if ($something->getAction() === Table::ACTION_CREATE) {
                $queries = $queryBuilder->createTable($something);
            } elseif ($something->getAction() === Table::ACTION_ALTER) {
                $queries = $queryBuilder->alterTable($something);
            } elseif ($something->getAction() === Table::ACTION_DROP) {
                $queries = $queryBuilder->dropTable($something);
            }
            if ($queries === null) {
                throw new InvalidArgumentException('Unknown action ' . $something->getAction());
            }
            $this->queries = array_merge($this->queries, $queries);
        }
    }

    /**
     * adds sql to list of queries to execute
     * @param string $sql
     */
    final protected function execute($sql)
    {
        $this->queries[] = $sql;
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
     * @return Table
     */
    final protected function table($name, $primaryKey = true, $charset = null, $collation = null)
    {
        $table = new Table($name, $primaryKey);
        $table->setCharset($charset ?: $this->adapter->getCharset());
        $table->setCollation($collation);

        $this->something[] = $table;
        return $table;
    }

    /**
     * @param string $name
     * @return AbstractMigration
     * @throws IncorrectMethodUsageException
     */
    final protected function dropColumn($name)
    {
        if ($this->table === null) {
            throw new IncorrectMethodUsageException('Wrong use of method dropColumn(). Use method table() first.');
        }
        $this->table->dropColumn($name);
        return $this;
    }

    /**
     * @param string|array $columns name(s) of column(s)
     * @param string $type type of index (unique, fulltext) default ''
     * @param string $method method of index (btree, hash) default ''
     * @param string $name name of index
     * @return AbstractMigration
     * @throws IncorrectMethodUsageException
     */
//    final protected function addIndex($columns, $type = Index::TYPE_NORMAL, $method = Index::METHOD_DEFAULT, $name = '')
//    {
//        if ($this->table === null) {
//            throw new IncorrectMethodUsageException('Wrong use of method addIndex(). Use method table() first.');
//        }
//        $this->table->addIndex(new Index($columns, $this->createIndexName($columns, $name), $type, $method));
//        return $this;
//    }

    /**
     * @param string|array $columns
     * @return AbstractMigration
     * @throws IncorrectMethodUsageException
     */
    final protected function dropIndex($columns)
    {
        if ($this->table === null) {
            throw new IncorrectMethodUsageException('Wrong use of method dropIndex(). Use method table() first.');
        }
        $this->table->dropIndex($this->createIndexName($columns));
        return $this;
    }

    /**
     * @param string $indexName
     * @return AbstractMigration
     * @throws IncorrectMethodUsageException
     */
    final protected function dropIndexByName($indexName)
    {
        if ($this->table === null) {
            throw new IncorrectMethodUsageException('Wrong use of method dropIndexByName(). Use method table() first.');
        }
        $this->table->dropIndex($indexName);
        return $this;
    }



    /**
     * @param string|array $columns
     * @param string $referencedTable
     * @param string|array $referencedColumns
     * @param string $onDelete
     * @param string $onUpdate
     * @return AbstractMigration
     * @throws IncorrectMethodUsageException
     */
//    final protected function addForeignKey($columns, $referencedTable, $referencedColumns = ['id'], $onDelete = ForeignKey::RESTRICT, $onUpdate = ForeignKey::RESTRICT)
//    {
//        if ($this->table === null) {
//            throw new IncorrectMethodUsageException('Wrong use of method addForeignKey(). Use method table() first.');
//        }
//        $this->table->addForeignKey(new ForeignKey($columns, $referencedTable, $referencedColumns, $onDelete, $onUpdate));
//        return $this;
//    }

    /**
     * @param string|array $columns
     * @return AbstractMigration
     * @throws IncorrectMethodUsageException
     */
    final protected function dropForeignKey($columns)
    {
        if ($this->table === null) {
            throw new IncorrectMethodUsageException('Wrong use of method dropForeignKey(). Use method table() first.');
        }
        $this->table->dropForeignKey($columns);
        return $this;
    }

    /**
     * @param string|array $columns
     * @return AbstractMigration
     */
    final protected function addPrimaryKey($columns)
    {
        $this->table->addPrimary($columns);
        return $this;
    }

    /**
     * @param string|array|Column $column
     * @return AbstractMigration
     * @throws IncorrectMethodUsageException
     */
    final protected function dropPrimaryKey($column = 'id')
    {
        if ($this->table === null) {
            throw new IncorrectMethodUsageException('Wrong use of method dropPrimaryKey(). Use method table() first.');
        }

        if (is_string($column)) {
            $column = new Column($column, 'integer');
        }

        if ($column instanceof Column) {
            $this->table->changeColumn($column->getName(), $column);
        }

        $this->table->dropPrimaryKey();
        return $this;
    }

    /**
     * generate create table queries
     * @throws IncorrectMethodUsageException if table() was not called first
     */
    final protected function create()
    {
        if ($this->table === null) {
            throw new IncorrectMethodUsageException('Wrong use of method create(). Use method table() first.');
        }

        $this->table->addPrimary($this->primaryKey);

        $this->tables[count($this->queries)] = $this->table;

        $queryBuilder = $this->adapter->getQueryBuilder();
        $queries = $queryBuilder->createTable($this->table);
        $this->queries = array_merge($this->queries, $queries);
        $this->table = null;
    }

    /**
     * generates drop table queries
     * @throws IncorrectMethodUsageException if table() was not called first
     */
    protected function drop()
    {
        if ($this->table === null) {
            throw new IncorrectMethodUsageException('Wrong use of method drop(). Use method table() first.');
        }

        $queryBuilder = $this->adapter->getQueryBuilder();
        $queries = $queryBuilder->dropTable($this->table);
        $this->queries = array_merge($this->queries, $queries);
        $this->table = null;
    }

    /**
     * generates rename table queries
     * @param string $newTableName
     * @throws IncorrectMethodUsageException
     */
    protected function rename($newTableName)
    {
        if ($this->table === null) {
            throw new IncorrectMethodUsageException('Wrong use of method drop(). Use method table() first.');
        }

        $queryBuilder = $this->adapter->getQueryBuilder();
        $queries = $queryBuilder->renameTable($this->table, $newTableName);
        $this->queries = array_merge($this->queries, $queries);
        $this->table = null;
    }

    /**
     * generates alter table query / queries
     * @throws IncorrectMethodUsageException if table() was not called first
     */
    final protected function save()
    {
        if ($this->table === null) {
            throw new IncorrectMethodUsageException('Wrong use of method save(). Use method table() first.');
        }
        $queryBuilder = $this->adapter->getQueryBuilder();
        $queries = $queryBuilder->alterTable($this->table);
        $this->queries = array_merge($this->queries, $queries);
        $this->table = null;
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
    private function runQueries($dry = false)
    {
        $results = [];
        try {
            if ($this->useTransaction && !$dry) {
                $this->adapter->startTransaction();
                $this->executedQueries[] = '::start transaction';
            }

            foreach ($this->queries as $query) {
                if (!$dry) {
                    $result = $this->adapter->execute($query);
                    $results[] = $result;
                }
                $this->executedQueries[] = $query instanceof PDOStatement ? $query->queryString : $query;
            }

            if ($this->useTransaction && !$dry) {
                $this->adapter->commit();
                $this->executedQueries[] = '::commit';
            }
        } catch (DatabaseQueryExecuteException $e) {
            if ($this->useTransaction) {
                $this->dbRollback();
            }
            $this->something = [];
            $this->queries = [];
            throw $e;
        }
        $this->something = [];
        $this->queries = [];
        return $results;
    }

    private function dbRollback()
    {
        $queriesExecuted = count($this->executedQueries);
        $this->adapter->rollback();
        $this->executedQueries[] = '::rollback';

        // own rollback for create table
        for ($i = $queriesExecuted; $i > 0; $i--) {
            $queryIndex = $i - 1;
            if (!($this->queries[$queryIndex] && isset($this->tables[$queryIndex]))) {
                continue;
            }
            $queryBuilder = $this->adapter->getQueryBuilder();
            $queries = $queryBuilder->dropTable($this->tables[$queryIndex]);
            foreach ($queries as $query) {
                $this->adapter->execute($query);
                $this->executedQueries[] = $query instanceof PDOStatement ? $query->queryString : $query;
            }
        }
    }

    /**
     * @return array
     */
    public function getExecutedQueries()
    {
        return $this->executedQueries;
    }
}
