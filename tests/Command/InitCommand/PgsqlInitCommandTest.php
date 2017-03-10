<?php

namespace Phoenix\Tests\Command\InitCommand;

class PgsqlInitCommandTest extends InitCommandTest
{
    protected function getEnvironment()
    {
        return 'pgsql';
    }
}
