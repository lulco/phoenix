<?php

namespace Phoenix\Tests\Command\RollbackCommand;

class MysqlRollbackCommandTest extends RollbackCommandTest
{
    protected function getEnvironment()
    {
        return 'mysql';
    }
}
