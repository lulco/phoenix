<?php

declare(strict_types=1);

namespace Phoenix\Migration;

use PDOStatement;
use Phoenix\Database\Adapter\AdapterInterface;
use Phoenix\Database\Element\ColumnSettings;
use Phoenix\Database\Element\MigrationTable;
use Phoenix\Database\Element\MigrationView;
use Phoenix\Database\Element\Structure;
use Phoenix\Database\Element\Table;
use Phoenix\Database\QueryBuilder\QueryBuilderInterface;
use Phoenix\Exception\DatabaseQueryExecuteException;
use Phoenix\Exception\InvalidArgumentValueException;
use ReflectionClass;

abstract class AbstractMigration
{
    private AdapterInterface $adapter;

    private string $datetime;

    private string $className;

    private string $fullClassName;

    /** @var array<int, string|PDOStatement|MigrationTable|MigrationView> */
    private array $queriesToExecute = [];

    /** @var string[] list of executed queries */
    private array $executedQueries = [];

    public function __construct(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
        $classNameCreator = new ClassNameCreator((string)(new ReflectionClass($this))->getFileName());
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

    /**
     * @return mixed[]
     * @throws DatabaseQueryExecuteException
     */
    final public function migrate(bool $dry = false): array
    {
        $this->reset();
        $this->up();
        $queries = $this->prepare();
        return $this->runQueries($queries, $dry);
    }

    /**
     * @return mixed[]
     * @throws DatabaseQueryExecuteException
     */
    final public function rollback(bool $dry = false): array
    {
        $this->reset();
        $this->down();
        $queries = $this->prepare();
        return $this->runQueries($queries, $dry);
    }

    final public function updateStructure(Structure $structure): void
    {
        $this->up();
        foreach ($this->queriesToExecute as $queryToExecute) {
            if ($queryToExecute instanceof MigrationTable) {
                $structure->update($queryToExecute);
            }
        }
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
     * @param string|null $charset
     * @param string|null $collation
     * @return MigrationTable
     */
    final protected function table(string $name, $primaryKey = true, ?string $charset = null, ?string $collation = null): MigrationTable
    {
        $table = new MigrationTable($name, $primaryKey);
        $table->setCharset($charset ?: $this->adapter->getCharset());
        $table->setCollation($collation ?: $this->adapter->getCollation());

        $this->queriesToExecute[] = $table;
        return $table;
    }

    final protected function tableExists(string $name): bool
    {
        return $this->adapter->getStructure()->getTable($name) !== null;
    }

    final protected function tableColumnExists(string $tableName, string $columnName): bool
    {
        $table = $this->getTable($tableName);
        if ($table === null) {
            return false;
        }
        return $table->getColumn($columnName) !== null;
    }

    final protected function tableIndexExists(string $tableName, string $indexName): bool
    {
        $table = $this->getTable($tableName);
        if ($table === null) {
            return false;
        }
        return $table->getIndex($indexName) !== null;
    }

    final protected function getTable(string $name): ?Table
    {
        return $this->adapter->getStructure()->getTable($name);
    }

    final protected function view(string $name): MigrationView
    {
        $migrationView = new MigrationView($name);
        $this->queriesToExecute[] = $migrationView;
        return $migrationView;
    }

    /**
     * @param string $sql
     * @return array<array<string, mixed>>
     */
    final protected function select(string $sql): array
    {
        return $this->adapter->select($sql);
    }

    /**
     * @param string $table
     * @param string[] $fields
     * @param array<string, mixed> $conditions
     * @param string[]|array<string, string> $orders column name => sort direction or list of columns (all will use ASC sorting)
     * @param string[] $groups
     * @return array<string, mixed>|null
     */
    final protected function fetch(string $table, array $fields = ['*'], array $conditions = [], array $orders = [], array $groups = []): ?array
    {
        return $this->adapter->fetch($table, $fields, $conditions, $orders, $groups);
    }

    /**
     * @param string $table
     * @param string[] $fields
     * @param array<string, mixed> $conditions
     * @param string|null $limit
     * @param string[]|array<string, string> $orders column name => sort direction or list of columns (all will use ASC sorting)
     * @param string[] $groups
     * @return array<array<string, mixed>>
     */
    final protected function fetchAll(string $table, array $fields = ['*'], array $conditions = [], ?string $limit = null, array $orders = [], array $groups = []): array
    {
        return $this->adapter->fetchAll($table, $fields, $conditions, $limit, $orders, $groups);
    }

    /**
     * adds insert query to list of queries to execute
     *
     * @param string $table
     * @param array<string, mixed>|array<array<string, mixed>> $data
     * @return AbstractMigration
     */
    final protected function insert(string $table, array $data): AbstractMigration
    {
        $this->execute($this->adapter->buildInsertQuery($table, $data));
        return $this;
    }

    /**
     * adds update query to list of queries to execute
     * @param string $table
     * @param array<string, mixed> $data
     * @param array<string, mixed> $conditions key => value conditions to generate WHERE part of query, imploded with AND
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
     * @param array<string, mixed> $conditions key => value conditions to generate WHERE part of query, imploded with AND
     * @param string $where additional where added to generated WHERE as is
     * @return AbstractMigration
     */
    final protected function delete(string $table, array $conditions = [], string $where = ''): AbstractMigration
    {
        $this->execute($this->adapter->buildDeleteQuery($table, $conditions, $where));
        return $this;
    }

    /**
     * adds turn off checking foreign keys query to list of queries to execute
     */
    final protected function checkForeignKeysOff(): void
    {
        $this->execute($this->adapter->buildDoNotCheckForeignKeysQuery());
    }

    /**
     * adds turn on checking foreign keys query to list of queries to execute
     */
    final protected function checkForeignKeysOn(): void
    {
        $this->execute($this->adapter->buildCheckForeignKeysQuery());
    }

    /**
     * changes collation on all existing tables and columns
     *
     * @throws InvalidArgumentValueException
     */
    final protected function changeCollation(string $targetCollation): void
    {
        $this->checkForeignKeysOff();
        [$targetCharset,] = explode('_', $targetCollation, 2);

        foreach ($this->adapter->getStructure()->getTables() as $table) {
            $migrationTable = $this->table($table->getName(), $table->getPrimary(), $targetCharset, $targetCollation);
            foreach ($table->getColumns() as $column) {
                $settings = $actualSettings = $column->getSettings()->getSettings();
                if (isset($actualSettings[ColumnSettings::SETTING_CHARSET]) && $actualSettings[ColumnSettings::SETTING_CHARSET] !== $targetCharset) {
                    $settings[ColumnSettings::SETTING_CHARSET] = $targetCharset;
                }
                if (isset($actualSettings[ColumnSettings::SETTING_COLLATION]) && $actualSettings[ColumnSettings::SETTING_COLLATION] !== $targetCollation) {
                    $settings[ColumnSettings::SETTING_COLLATION] = $targetCollation;
                }
                if ($settings !== $actualSettings) {
                    /** @var array{null?: bool, default?: mixed, length?: int, decimals?: int, signed?: bool, autoincrement?: bool, after?: string, first?: bool, charset?: string, collation?: string, values?: array<int|string, int|string>, comment?: string} $settings */
                    $migrationTable->changeColumn($column->getName(), $column->getName(), $column->getType(), $settings);
                }
            }
            $migrationTable->save();
        }
        $this->checkForeignKeysOn();
    }

    /**
     * @param array<int, string|PDOStatement> $queries
     * @return mixed[]
     * @throws DatabaseQueryExecuteException
     */
    private function runQueries(array $queries, bool $dry = false): array
    {
        $results = [];
        foreach ($queries as $query) {
            if (!$dry) {
                $result = $query instanceof PDOStatement ? $this->adapter->execute($query) : $this->adapter->query($query)->fetchAll();
                $results[] = $result;
            }
            $this->executedQueries[] = $query instanceof PDOStatement ? $query->queryString : $query;
        }
        return $results;
    }

    /**
     * @return string[]
     */
    public function getExecutedQueries(): array
    {
        return $this->executedQueries;
    }

    abstract protected function up(): void;

    abstract protected function down(): void;

    private function reset(): void
    {
        $this->queriesToExecute = [];
        $this->executedQueries = [];
    }

    /**
     * @return array<int, string|PDOStatement>
     */
    private function prepare(): array
    {
        $queryBuilder = $this->adapter->getQueryBuilder();
        $queries = [];
        foreach ($this->queriesToExecute as $queryToExecute) {
            if ($queryToExecute instanceof MigrationTable) {
                $queries = array_merge($queries, $this->prepareMigrationTableQueries($queryToExecute, $queryBuilder));
                continue;
            }
            if ($queryToExecute instanceof MigrationView) {
                $queries = array_merge($queries, $this->prepareMigrationViewQueries($queryToExecute, $queryBuilder));
                continue;
            }
            $queries[] = $queryToExecute;
        }
        return $queries;
    }

    /**
     * @return array<string|PDOStatement>
     */
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

    /**
     * @return array<string>
     */
    private function prepareMigrationViewQueries(MigrationView $view, QueryBuilderInterface $queryBuilder): array
    {
        if ($view->getAction() === MigrationView::ACTION_CREATE) {
            return $queryBuilder->createView($view);
        }

        if ($view->getAction() === MigrationView::ACTION_REPLACE) {
            return $queryBuilder->replaceView($view);
        }

        if ($view->getAction() === MigrationView::ACTION_DROP) {
            return $queryBuilder->dropView($view);
        }

        return [];
    }
}
