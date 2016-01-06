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
    
    public function __construct(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }
    
    public function migrate()
    {
        $this->up();
        $results = [];
        foreach ($this->queries as $query) {
            $results[] = $this->adapter->execute($query);
        }
        return $results;
    }
    
    public function rollback()
    {
        $this->down();
        $results = [];
        foreach ($this->queries as $query) {
            $results[] = $this->adapter->execute($query);
        }
        return $results;
    }
    
    abstract protected function up();

    abstract protected function down();
    
    protected function execute($sql)
    {
        $this->queries[] = $sql;
    }

    protected function table($name, $primaryColumn = true)
    {
        if ($this->table !== null) {
            throw new IncorrectMethodUsageException('Wrong use of method table(). Use one of methods create(), drop() first.');
        }
        $this->table = new Table($name, $primaryColumn);
        return $this;
    }
    
    protected function addColumn(
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
    
    protected function addIndex($columns, $type = Index::TYPE_NORMAL, $method = Index::METHOD_DEFAULT)
    {
        if ($this->table === null) {
            throw new IncorrectMethodUsageException('Wrong use of method addIndex(). Use method table() first.');
        }
        $this->table->addIndex($columns, $type, $method);
        return $this;
    }
    
    protected function create()
    {
        if ($this->table === null) {
            throw new IncorrectMethodUsageException('Wrong use of method create(). Use method table() first.');
        }
        $queryBuilder = $this->adapter->getQueryBuilder();
        $this->queries[] = $queryBuilder->createTable($this->table);
        $this->table = null;
    }
    
    protected function drop()
    {
        if ($this->table === null) {
            throw new IncorrectMethodUsageException('Wrong use of method drop(). Use method table() first.');
        }
        $queryBuilder = $this->adapter->getQueryBuilder();
        $this->queries[] = $queryBuilder->dropTable($this->table);
        $this->table = null;
    }
}
