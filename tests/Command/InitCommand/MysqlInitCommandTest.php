<?php

namespace Phoenix\Tests\Command\InitCommand;

class MysqlInitCommandTest extends InitCommandTest
{
    protected function getEnvironment()
    {
        return 'mysql';
    }
}
