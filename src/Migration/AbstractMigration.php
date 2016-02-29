<?php

namespace Phoenix\Migration;

use InvalidArgumentException;
use Phoenix\Database\Adapter\AdapterInterface;
use Phoenix\Database\Element\Column;
use Phoenix\Database\Element\ForeignKey;
use Phoenix\Database\Element\Index;
use Phoenix\Database\Element\Table;
use Phoenix\Exception\DatabaseQueryExecuteException;
use Phoenix\Exception\IncorrectMethodUsageException;
use ReflectionClass;
use RuntimeException;

/**
 * @method AbstractMigration addColumn(string $name name of column, string $type type of column, boolean $allowNull=false nullable column, mixed $default=null default value for column, int|null $length=null length of column, int|null $decimals=null number of decimals in decimal/float/double column, boolean $signed=true signed column, boolean $autoincrement=false autoincrement column) Adds column to the table @throws IncorrectMethodUsageException @deprecated since version 1.0.0
 * @method AbstractMigration addColumn(string $name name of column, string $type type of column, array $settings=[] settings for column ('null'; 'default'; 'length'; 'decimals'; 'signed'; 'autoincrement'; 'after'; 'first';)) Adds column to the table @throws IncorrectMethodUsageException
 * @method AbstractMigration addColumn(Column $column column definition) Adds column to the table - @throws IncorrectMethodUsageException
 * @method AbstractMigration changeColumn(string $oldName old name of column, string $name new name of column, string $type type of column, boolean $allowNull=false nullable column, mixed $default=null default value for column) Changes column in the table to new one @throws IncorrectMethodUsageException
 * @method AbstractMigration changeColumn(string $oldName old name of column, string $name new name of column, string $type type of column, array $settings=[] settings for column ('null'; 'default'; 'length'; 'decimals'; 'signed'; 'autoincrement'; 'after'; 'first';)) Changes column in the table to new one @throws IncorrectMethodUsageException
 * @method AbstractMigration changeColumn(string $oldName old name of column, Column $column new column definition) Changes column in the table to new one @throws IncorrectMethodUsageException
 */
abstract class AbstractMigration
{
    /** @var string */
    private $datetime;
    
    /** @var string */
    private $className;
    
    /** @var string */
    private $fullClassName;
    
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
        return $this->className;
    }
    
    /**
     * @return string
     */
    final public function getFullClassName()
    {
        return $this->fullClassName;
    }
    
    /**
     * @return array
     */
    final public function migrate()
    {
        $this->up();
        return $this->runQueries();
    }
    
    /**
     * @return array
     */
    final public function rollback()
    {
        $this->down();
        return $this->runQueries();
    }
    
    /**
     * need override
     */
    abstract protected function up();

    /**
     * need override
     */
    abstract protected function down();
    
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
     * @return AbstractMigration
     * @throws IncorrectMethodUsageException
     */
    final protected function table($name, $primaryKey = true)
    {
        if ($this->table !== null) {
            throw new IncorrectMethodUsageException('Wrong use of method table(). Use one of methods create(), drop(), save() first.');
        }
        
        $this->primaryKey = $primaryKey;
        
        $this->table = new Table($name);
        return $this;
    }
    
    public function __call($name, $arguments)
    {
        if ($name == 'addColumn') {
            return $this->_addColumn($arguments);
        }
        if ($name == 'changeColumn') {
            return $this->_changeColumn($arguments);
        }
        throw new RuntimeException('Method "' . $name . '" not found');
    }
    
    private function _addColumn($arguments)
    {
        if ($this->table === null) {
            throw new IncorrectMethodUsageException('Wrong use of method addColumn(). Use method table() first.');
        }
        
        if (count($arguments) > 4) {
            echo 'Method addColumn(string $name, string $type, boolean $allowNull = false, mixed $default = null, int|null $length = null, int|null $decimals = null, boolean $signed = true, boolean $autoincrement = false) will be deprecated in version 1.0.0' . "\n";
        }
        
        if ($arguments[0] instanceof Column) {
            return $this->addPreparedColumn($arguments[0]);
        }
        
        if (count($arguments) == 3 && is_array($arguments[2])) {
            return $this->addComplexColumn($arguments[0], $arguments[1], $arguments[2]);
        }
        
        return $this->addSimpleColumn(
            $arguments[0],
            $arguments[1],
            isset($arguments[2]) ? $arguments[2] : false,
            isset($arguments[3]) ? $arguments[3] : null,
            isset($arguments[4]) ? $arguments[4] : null,
            isset($arguments[5]) ? $arguments[5] : null,
            isset($arguments[6]) ? $arguments[6] : true,
            isset($arguments[7]) ? $arguments[7] : false
        );
    }
    
    private function addSimpleColumn(
        $name,
        $type,
        $allowNull = false,
        $default = null,
        $length = null,
        $decimals = null,
        $signed = true,
        $autoincrement = false
    ) {
        $column = new Column(
            $name,
            $type,
            [
                'null' => $allowNull,
                'default' => $default,
                'length' => $length,
                'decimals' => $decimals,
                'signed' => $signed,
                'autoincrement' => $autoincrement,
            ]
        );
        return $this->addPreparedColumn($column);
    }
    
    private function addComplexColumn($name, $type, $settings = [])
    {
        $column = new Column($name, $type, $settings);
        return $this->addPreparedColumn($column);
    }
    
    private function addPreparedColumn(Column $column)
    {
        $this->table->addColumn($column);
        return $this;
    }
    
    private function _changeColumn($arguments)
    {
        if ($this->table === null) {
            throw new IncorrectMethodUsageException('Wrong use of method changeColumn(). Use method table() first.');
        }
        
        if (count($arguments) > 5) {
            throw new InvalidArgumentException('Too many arguments');
        }
        
        if ($arguments[1] instanceof Column) {
            return $this->changePreparedColumn($arguments[0], $arguments[1]);
        }
        
        if (count($arguments) == 4 && is_array($arguments[3])) {
            return $this->changeComplexColumn($arguments[0], $arguments[1], $arguments[2], $arguments[3]);
        }
        
        return $this->changeSimpleColumn(
            $arguments[0],
            $arguments[1],
            $arguments[2],
            isset($arguments[3]) ? $arguments[3] : false,
            isset($arguments[4]) ? $arguments[4] : null
        );
    }
    
    private function changePreparedColumn($oldName, Column $newColumn)
    {
        $this->table->changeColumn($oldName, $newColumn);
        return $this;
    }

    private function changeSimpleColumn($oldName, $newName, $newType, $allowNull = false, $default = null)
    {
        $newColumn = new Column($newName, $newType, ['null' => $allowNull, 'default' => $default]);
        return $this->changePreparedColumn($oldName, $newColumn);
    }
    
    private function changeComplexColumn($oldName, $newName, $newType, array $settings = [])
    {
        $newColumn = new Column($newName, $newType, $settings);
        return $this->changePreparedColumn($oldName, $newColumn);
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
     * @return AbstractMigration
     * @throws IncorrectMethodUsageException
     */
    final protected function addIndex($columns, $type = Index::TYPE_NORMAL, $method = Index::METHOD_DEFAULT)
    {
        if ($this->table === null) {
            throw new IncorrectMethodUsageException('Wrong use of method addIndex(). Use method table() first.');
        }
        
        $this->table->addIndex(new Index($columns, $type, $method));
        return $this;
    }
    
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
        $this->table->dropIndex($columns);
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
    final protected function addForeignKey($columns, $referencedTable, $referencedColumns = ['id'], $onDelete = ForeignKey::RESTRICT, $onUpdate = ForeignKey::RESTRICT)
    {
        if ($this->table === null) {
            throw new IncorrectMethodUsageException('Wrong use of method addForeignKey(). Use method table() first.');
        }
        $this->table->addForeignKey(new ForeignKey($columns, $referencedTable, $referencedColumns, $onDelete, $onUpdate));
        return $this;
    }
    
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
        
        if (!$column instanceof Column) {
            $column = new Column($column, 'integer');
        }
        
        
        $this->table->changeColumn($column->getName(), $column);
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
    
    private function runQueries()
    {
        $results = [];
        try {
            if ($this->useTransaction) {
                $this->adapter->startTransaction();
                $this->executedQueries[] = '::start transaction';
            }
            
            foreach ($this->queries as $query) {
                $result = $this->adapter->execute($query);
                $this->executedQueries[] = $query;
                $results[] = $result;
            }
            
            if ($this->useTransaction) {
                $this->adapter->commit();
                $this->executedQueries[] = '::commit';
            }
        } catch (DatabaseQueryExecuteException $e) {
            if ($this->useTransaction) {
                $this->dbRollback();
            }
            $this->queries = [];
            throw $e;
        }
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
                $this->executedQueries[] = $query;
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
