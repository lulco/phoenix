<?php

namespace Phoenix\Migration;

use Phoenix\Database\Adapter\AdapterInterface;
use Phoenix\Exception\IncorrectMethodUsageException;
use Phoenix\QueryBuilder\Index;
use Phoenix\QueryBuilder\Table;

abstract class AbstractMigration
{
    /** @var Table */
    private $table = null;
    
    /** @var array list of queries to run */
    private $queries = [];
    
    /** @var AdapterInterface */
    private $adapter;
    
    final public function __construct(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }
    
    final public function migrate()
    {
        $this->up();
        $results = [];
        foreach ($this->queries as $query) {
            $results[] = $this->adapter->execute($query);
        }
        $this->queries = [];
        return $results;
    }
    
    final public function rollback()
    {
        $this->down();
        $results = [];
        foreach ($this->queries as $query) {
            $results[] = $this->adapter->execute($query);
        }
        $this->queries = [];
        return $results;
    }
    
    abstract protected function up();

    abstract protected function down();
    
    final protected function execute($sql)
    {
        $this->queries[] = $sql;
    }

    final protected function table($name, $primaryColumn = true)
    {
        if ($this->table !== null) {
            throw new IncorrectMethodUsageException('Wrong use of method table(). Use one of methods create(), drop() first.');
        }
        $this->table = new Table($name, $primaryColumn);
        return $this;
    }
    
    /**
     * @param string $name name of column
     * @param string $type type of column
     * @param boolean $allowNull default false
     * @param mixed $default default null
     * @param int|null $length length of column, null if you want use default length by column type
     * @param int|null $decimals number of decimals in numeric types (float, double, decimal etc.)
     * @param boolean $signed default true
     * @param boolean $autoincrement default false
     * @return AbstractMigration
     * @throws IncorrectMethodUsageException
     */
    final protected function addColumn(
        $name,
        $type,
        $allowNull = false,
        $default = null,
        $length = null,
        $decimals = null,
        $signed = true,
        $autoincrement = false
    ) {
        if ($this->table === null) {
            throw new IncorrectMethodUsageException('Wrong use of method addColumn(). Use method table() first.');
        }
        $this->table->addColumn($name, $type, $allowNull, $default, $length, $decimals, $signed, $autoincrement);
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
        $this->table->addIndex($columns, $type, $method);
        return $this;
    }
    
    final protected function create()
    {
        if ($this->table === null) {
            throw new IncorrectMethodUsageException('Wrong use of method create(). Use method table() first.');
        }
        $queryBuilder = $this->adapter->getQueryBuilder();
        $this->queries[] = $queryBuilder->createTable($this->table);
        $this->table = null;
    }
    
    final protected function drop()
    {
        if ($this->table === null) {
            throw new IncorrectMethodUsageException('Wrong use of method drop(). Use method table() first.');
        }
        $queryBuilder = $this->adapter->getQueryBuilder();
        $this->queries[] = $queryBuilder->dropTable($this->table);
        $this->table = null;
    }
}
