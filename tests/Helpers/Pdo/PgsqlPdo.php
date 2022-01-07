<?php

declare(strict_types=1);

namespace Phoenix\Tests\Helpers\Pdo;

use PDO;

final class PgsqlPdo extends PDO
{
    public function __construct(?string $db = null)
    {
        $dsn = 'pgsql:';
        if ($db) {
            $dsn .= 'dbname=' . $db;
        }
        if (getenv('PHOENIX_PGSQL_HOST')) {
            $dsn .= ';host=' . getenv('PHOENIX_PGSQL_HOST');
        }
        if (getenv('PHOENIX_PGSQL_PORT')) {
            $dsn .= ';port=' . getenv('PHOENIX_PGSQL_PORT');
        }
        if (getenv('PHOENIX_PGSQL_CHARSET')) {
            $dsn .= ';options=\'--client_encoding=' . getenv('PHOENIX_PGSQL_CHARSET') . '\'';
        }
        parent::__construct($dsn, getenv('PHOENIX_PGSQL_USERNAME'), getenv('PHOENIX_PGSQL_PASSWORD'), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT
        ]);
    }
}
