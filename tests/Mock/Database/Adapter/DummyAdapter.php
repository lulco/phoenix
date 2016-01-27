<?php

namespace Phoenix\Tests\Mock\Database\Adapter;

use Phoenix\Database\Adapter\AdapterInterface;
use Phoenix\Exception\DatabaseQueryExecuteException;

abstract class DummyAdapter implements AdapterInterface
{
    private $queryList = [];
    
    public function execute($sql)
    {
        if (isset($this->queryList[md5($sql)])) {
            throw new DatabaseQueryExecuteException();
        }
        $this->queryList[md5($sql)] = $sql;
        return 'Query ' . $sql . ' executed';
    }

    public function insert($table, array $data)
    {
        $sql = 'INSERT INTO ' . $table . '(' . array_keys($data) . ') VALUES (' . implode(', ', $data) . ');';
        return $this->execute($sql);
    }
    
    public function startTransaction()
    {
        return true;
    }
    
    public function commit()
    {
        return true;
    }

    public function rollback()
    {
        return true;
    }
}
