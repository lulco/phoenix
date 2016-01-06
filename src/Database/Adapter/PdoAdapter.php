<?php

namespace Phoenix\Database\Adapter;

use PDO;
use Phoenix\Exception\DatabaseQueryExecuteException;

abstract class PdoAdapter implements AdapterInterface
{
    private $pdo;
    
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }
    
    public function execute($sql)
    {
        $res = $this->pdo->query($sql);
        if ($res !== false) {
            return $sql;
        }
        $errorInfo = $this->pdo->errorInfo();
        throw new DatabaseQueryExecuteException('SQLSTATE[' . $errorInfo[0] . ']: ' . $errorInfo[2], $errorInfo[1]);
    }
}
