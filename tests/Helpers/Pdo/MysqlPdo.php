<?php

namespace Phoenix\Tests\Helpers\Pdo;

use PDO;

class MysqlPdo extends PDO
{
    public function __construct($db = null)
    {
        $dsnParts = [];
        if ($db) {
            $dsnParts[] = 'dbname=' . $db;
        }
        if (getenv('PHOENIX_MYSQL_HOST')) {
            $dsnParts[] = 'host=' . getenv('PHOENIX_MYSQL_HOST');
        }
        if (getenv('PHOENIX_MYSQL_PORT')) {
            $dsnParts[] = 'port=' . getenv('PHOENIX_MYSQL_PORT');
        }
        if (getenv('PHOENIX_MYSQL_CHARSET')) {
            $dsnParts[] = 'charset=' . getenv('PHOENIX_MYSQL_CHARSET');
        }

        $dsn = 'mysql:' . implode(';', $dsnParts);
        parent::__construct($dsn, getenv('PHOENIX_MYSQL_USERNAME'), getenv('PHOENIX_MYSQL_PASSWORD') ?: null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT
        ]);
    }
}
