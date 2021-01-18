<?php

namespace Phoenix\Tests\Helpers\Pdo;

use PDO;

class MysqlPdo extends PDO
{
    public function __construct($db = null)
    {
        $dsn = 'mysql:';
        if ($db) {
            $dsn .= 'dbname=' . $db;
        }
        if (getenv('PHOENIX_MYSQL_HOST')) {
            $dsn .= ';host=' . getenv('PHOENIX_MYSQL_HOST');
        }
        if (getenv('PHOENIX_MYSQL_PORT')) {
            $dsn .= ';port=' . getenv('PHOENIX_MYSQL_PORT');
        }
        if (getenv('PHOENIX_MYSQL_CHARSET')) {
            $dsn .= ';charset=' . getenv('PHOENIX_MYSQL_CHARSET');
        }

        var_dump('DB name:' . $db);
        var_dump('MySql DSN: ' . $dsn);
        var_dump('Username: ' . getenv('PHOENIX_MYSQL_USERNAME'));
        var_dump('Password: ' . getenv('PHOENIX_MYSQL_PASSWORD'));

        parent::__construct($dsn, getenv('PHOENIX_MYSQL_USERNAME'), getenv('PHOENIX_MYSQL_PASSWORD'), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT
        ]);
    }
}
