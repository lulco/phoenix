<?php

namespace Phoenix\Database\Adapter;

use PDO;
use Phoenix\Exception\DatabaseQueryExecuteException;
use Phoenix\Database\QueryBuilder\QueryBuilderInterface;

abstract class PdoAdapter implements AdapterInterface
{
    private $pdo;
    
    /** @var QueryBuilderInterface */
    protected $queryBuilder;
    
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->getQueryBuilder();
    }
    
    /**
     * @param string $sql
     * @return string
     * @throws DatabaseQueryExecuteException on error
     */
    public function execute($sql)
    {
        $res = $this->pdo->query($sql);
        if ($res !== false) {
            return $res;
        }
        
        $this->throwError($sql);
    }

    public function insert($table, array $data)
    {
        $values = [];
        foreach (array_keys($data) as $key) {
            $values[] = ':' . $key;
        }

        $query = 'INSERT INTO ' . $this->queryBuilder->escapeString($table) . ' (' . implode(', ', array_keys($data)) . ') VALUES (' . implode(', ', $values) . ')';
        $statement = $this->pdo->prepare($query);
        if (!$statement) {
            $this->throwError($query);
        }
        $res = $statement->execute($data);
        if ($res !== false) {
            return $this->pdo->lastInsertId();
        }
        $this->throwError($statement->queryString);
    }
    
    public function startTransaction()
    {
        return $this->pdo->beginTransaction();
    }
    
    public function commit()
    {
        return $this->pdo->commit();
    }

    public function rollback()
    {
        return $this->pdo->rollBack();
    }
    
    private function throwError($query)
    {
        $errorInfo = $this->pdo->errorInfo();
        throw new DatabaseQueryExecuteException('SQLSTATE[' . $errorInfo[0] . ']: ' . $errorInfo[2] . '. Query ' . $query . ' fails', $errorInfo[1]);
    }
}
