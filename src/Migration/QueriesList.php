<?php


namespace Phoenix\Migration;

use ArrayIterator;
use IteratorAggregate;
use PDOStatement;
use Phoenix\Database\Adapter\AdapterInterface;
use Phoenix\Database\Element\MigrationTable;
use Phoenix\Database\QueryBuilder\QueryBuilderInterface;

/**
 * This class is used for generating and storing queries list for migration
 *
 * @package Phoenix\Migration
 */
class QueriesList implements IteratorAggregate
{
    /** @var AdapterInterface */
    private $adapter;

    /** @var array */
    private $queriesToExecute = [];

    /**
     * QueriesList constructor.
     *
     * @param AdapterInterface $adapter
     */
    public function __construct(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
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
     * @param string|null $charset
     * @param string|null $collation
     * @return MigrationTable
     */
    final public function table(string $name, $primaryKey = true, ?string $charset = null, ?string $collation = null): MigrationTable
    {
        $table = new MigrationTable($name, $primaryKey);
        $table->setCharset($charset ?: $this->adapter->getCharset());
        $table->setCollation($collation);

        $this->queriesToExecute[] = $table;
        return $table;
    }

    /**
     * @param string|PDOStatement $sql
     */
    final public function execute($sql): void
    {
        $this->queriesToExecute[] = $sql;
    }

    /**
     * adds insert query to list of queries to execute
     *
     * @param string $table
     * @param array $data
     */
    final public function insert(string $table, array $data)
    {
        $this->execute($this->adapter->buildInsertQuery($table, $data));
    }

    /**
     * adds update query to list of queries to execute
     * @param string $table
     * @param array $data
     * @param array $conditions key => value conditions to generate WHERE part of query, imploded with AND
     * @param string $where additional where added to generated WHERE as is
     */
    final public function update(string $table, array $data, array $conditions = [], string $where = '')
    {
        $this->execute($this->adapter->buildUpdateQuery($table, $data, $conditions, $where));
    }

    /**
     * adds delete query to list of queries to exectue
     * @param string $table
     * @param array $conditions key => value conditions to generate WHERE part of query, imploded with AND
     * @param string $where additional where added to generated WHERE as is
     */
    final public function delete(string $table, array $conditions = [], string $where = '')
    {
        $this->execute($this->adapter->buildDeleteQuery($table, $conditions, $where));
    }

    public function prepare(): array
    {
        $queryBuilder = $this->adapter->getQueryBuilder();
        $queries = [];
        foreach ($this->queriesToExecute as $queryToExecute) {
            if (!$queryToExecute instanceof MigrationTable) {
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
        } elseif ($table->getAction() === MigrationTable::ACTION_TRUNCATE) {
            $tableQueries = $queryBuilder->truncateTable($table);
        }
        return $tableQueries;
    }

    public function reset(): void
    {
        $this->queriesToExecute = [];
    }

    public function getIterator()
    {
        return new ArrayIterator($this->queriesToExecute);
    }
}
