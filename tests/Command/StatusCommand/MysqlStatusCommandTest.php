<?php

namespace Phoenix\Tests\Command\StatusCommand;

class MysqlStatusCommandTest extends StatusCommandTest
{
    protected function getEnvironment()
    {
        return 'mysql';
    }
}
