<?php

namespace Phoenix\Tests;

use Phoenix\Command\CleanupCommand;
use Phoenix\Command\CreateCommand;
use Phoenix\Command\InitCommand;
use Phoenix\Command\MigrateCommand;
use Phoenix\Command\RollbackCommand;
use Phoenix\Tests\Command\Input;
use Phoenix\Tests\Command\Output;
use PHPUnit_Framework_TestCase;

class CommandTest extends PHPUnit_Framework_TestCase
{
    public function testInit()
    {
        $command = new InitCommand();
        // TODO set custom config
        $this->assertEquals('init', $command->getName());
        $command->run(new Input(), new Output());
        
        // TODO test if table phoenix_log exists
    }
    
    public function testCreate()
    {
        $command = new CreateCommand();
        // TODO set custom config
        $input = new Input();
        $filename = __DIR__ . '/../testing_migrations/provys/' . date('YmdHis') . '_add_something_to_table.php';
        $this->assertFileNotExists($filename);
        
        $input->setArgument('migration', 'MyNamespace\AddSomethingToTable');
        $command->run($input, new Output());
        
        $this->assertFileExists($filename);
        unlink($filename);
        $this->assertFileNotExists($filename);
    }
    
    public function testMultipleInitWithCustomConfig()
    {
        $command = new InitCommand();
        
        $configuration = [
            'migration_dirs' => [
                'first_dir',
                'second_dir'
            ],
            'environments' => [
                'mysql' => [
                    'adapter' => 'mysql',
                    'host' => 'localhost',
                    'username' => 'root',
                    'password' => '123',
                    'db_name' => 'libs',
                    'charset' => 'utf8',
                ],
            ],
        ];
        $this->assertInstanceOf('\Phoenix\Command\AbstractCommand', $command->setConfig($configuration));
        $this->setExpectedException('\Phoenix\Exception\WrongCommandException', 'Phoenix was already initialized, run migrate or rollback command now.');
        $command->run(new Input(), new Output());
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
        $command = new CleanupCommand();
        // TODO set custom config
        $command->run(new Input(), new Output());
        
        // TODO test if table phoenix_log exists and there are some migrations - less then before rollback
    }
    
    public function testCleanupWithRollbacks()
    {
        $command = new InitCommand();
        // TODO set custom config
        $this->assertEquals('init', $command->getName());
        $command->run(new Input(), new Output());
        
        $command = new MigrateCommand();
        // TODO set custom config
        $this->assertEquals('migrate', $command->getName());
        $command->run(new Input(), new Output());
        
        $command = new CleanupCommand();
        // TODO set custom config
        $command->run(new Input(), new Output());
    }
}
