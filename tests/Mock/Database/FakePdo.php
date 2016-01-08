<?php

namespace Phoenix\Tests\Database;

use PDO;

class FakePdo extends PDO
{
    public function __construct()
    {
    }
}
