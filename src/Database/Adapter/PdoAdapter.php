<?php

namespace Phoenix\Database\Adapter;

use PDO;
use Phoenix\Exception\DatabaseQueryExecuteException;
use Phoenix\QueryBuilder\QueryBuilderInterface;

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
        $errorInfo = $this->pdo->errorInfo();
        throw new DatabaseQueryExecuteException('SQLSTATE[' . $errorInfo[0] . ']: ' . $errorInfo[2] . '. Query ' . $sql . ' fails', $errorInfo[1]);
    }

    public function insert($table, array $data)
    {
        $values = [];
        foreach (array_keys($data) as $key) {
            $values[] = ':' . $key;
        }

        $statement = $this->pdo->prepare('INSERT INTO ' . $this->queryBuilder->escapeString($table) . '(' . implode(', ', array_keys($data)) . ') VALUES (' . implode(', ', $values) . ')');
        $res = $statement->execute($data);
        if ($res !== false) {
            return $this->pdo->lastInsertId();
        }
        
        $errorInfo = $this->pdo->errorInfo();
        throw new DatabaseQueryExecuteException('SQLSTATE[' . $errorInfo[0] . ']: ' . $errorInfo[2] . '. Query ' . $statement->queryString . ' fails', $errorInfo[1]);
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
}
