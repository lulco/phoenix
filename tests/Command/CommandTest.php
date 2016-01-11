<?php

namespace Phoenix\Tests;

use Phoenix\Command\InitCommand;
use Phoenix\Command\MigrateCommand;
use Phoenix\Command\RollbackCommand;
use Phoenix\Tests\Command\Input;
use Phoenix\Tests\Command\Output;
use PHPUnit_Framework_TestCase;

class CommandTest extends PHPUnit_Framework_TestCase
{
    public function testMigrateWithoutInit()
    {
        $command = new MigrateCommand();
        $this->setExpectedException('\Phoenix\Exception\WrongCommandException', 'Phoenix is not initialized, run init command first.');
        $command->run(new Input(), new Output());
    }
    
    public function testRollbackWithoutInit()
    {
        $command = new RollbackCommand();
        $this->setExpectedException('\Phoenix\Exception\WrongCommandException', 'Phoenix is not initialized, run init command first.');
        $command->run(new Input(), new Output());
    }
    
    public function testInit()
    {
        $command = new InitCommand();
        // TODO set custom config
        $this->assertEquals('init', $command->getName());
        $command->run(new Input(), new Output());
        
        // TODO test if table phoenix_log exists
    }
    
    public function testCustomNameAndMultipleInit()
    {
        $command = new InitCommand('phoenix:init');
        // TODO set custom config
        $this->assertEquals('phoenix:init', $command->getName());
        $this->setExpectedException('\Phoenix\Exception\WrongCommandException', 'Phoenix was already initialized, run migrate or rollback command now.');
        $command->run(new Input(), new Output());
    }
    
    public function testMigrate()
    {
        $command = new MigrateCommand();
        // TODO set custom config
        $this->assertEquals('migrate', $command->getName());
        $command->run(new Input(), new Output());
        
        // TODO test if table phoenix_log exists and there are some migrations
    }
    
    public function testMigrateWhenNothingToMigrate()
    {
        $command = new MigrateCommand();
        // TODO set custom config
        $output = new Output();
        $this->assertEquals(0, $command->run(new Input(), $output));
        // TODO test output messages
        // TODO test if table phoenix_log exists and there are some migrations
    }
    
    public function testRollback()
    {
        $command = new RollbackCommand();
        // TODO set custom config
        $this->assertEquals('rollback', $command->getName());
        $command->run(new Input(), new Output());
        
        // TODO test if table phoenix_log exists and there are some migrations - less then before rollback
    }
    
    public function testMultipleRollback()
    {
        // 5 = count of migrations
        for ($i = 0; $i <= 5; $i++) {
            $command = new RollbackCommand();
            // TODO set custom config
            $this->assertEquals('rollback', $command->getName());
            $command->run(new Input(), new Output());
        }
        
        // TODO test if table phoenix_log exists and there are no migrations
    }
    
    public function testCleanup()
    {
        $command = new \Phoenix\Command\CleanupCommand();
        // TODO set custom config
        $command->run(new Input(), new Output());
        
        // TODO test if table phoenix_log exists and there are some migrations - less then before rollback
    }
}
