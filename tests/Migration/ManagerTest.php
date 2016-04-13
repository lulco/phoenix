<?php

namespace Phoenix\Tests\Migration;

use PDO;
use Phoenix\Config\Config;
use Phoenix\Database\Adapter\SqliteAdapter;
use Phoenix\Migration\Init\Init;
use Phoenix\Migration\Manager;
use PHPUnit_Framework_TestCase;

class ManagerTest extends PHPUnit_Framework_TestCase
{
    private $manager;
    
    private $initMigration;
    
    protected function setUp()
    {
        $config = new Config([
            'migration_dirs' => [
                __DIR__ . '/../fake/structure/migration_directory_1/',
            ],
            'environments' => [
                'sqlite' => [
                    'adapter' => 'sqlite',
                    'dsn' => 'sqlite::memory:'
                ],
            ]
        ]);
        $environmentConfig = $config->getEnvironmentConfig('sqlite');
        $pdo = new PDO($environmentConfig->getDsn());
        $adapter = new SqliteAdapter($pdo);
        $this->manager = new Manager($config, $adapter);
        
        $this->initMigration = new Init($adapter, $config->getLogTableName());
        $this->initMigration->migrate();
    }
    
    public function testMigrations()
    {
        $executedMigrations = $this->manager->executedMigrations();
        $this->assertTrue(is_array($executedMigrations));
        $this->assertCount(0, $executedMigrations);
        
        $migrations = $this->manager->findMigrationsToExecute();
        $this->assertTrue(is_array($migrations));
        
        $firstUpMigration = $this->manager->findMigrationsToExecute('up', 'first');
        $this->checkMigrations($firstUpMigration, 1);
        
        $downMigrations = $this->manager->findMigrationsToExecute('down');
        $this->checkMigrations($downMigrations, 0);
        
        $count = 0;
        foreach ($migrations as $migration) {
            $migration->migrate();
            $this->manager->logExecution($migration);
            $count++;
            $this->assertTrue(is_array($this->manager->executedMigrations()));
            $this->assertCount($count, $this->manager->executedMigrations());
            
            $migration->rollback();
            $this->manager->removeExecution($migration);
            $count--;
            $this->assertTrue(is_array($this->manager->executedMigrations()));
            $this->assertCount($count, $this->manager->executedMigrations());
            
            $migration->migrate();
            $this->manager->logExecution($migration);
            $count++;
            $this->assertTrue(is_array($this->manager->executedMigrations()));
            $this->assertCount($count, $this->manager->executedMigrations());
        }
        
        $firstDownMigration = $this->manager->findMigrationsToExecute('down', 'first');
        $this->checkMigrations($firstDownMigration, 1);
        
        $downMigrations = $this->manager->findMigrationsToExecute('down');
        $this->checkMigrations($downMigrations, $count);
        
        $this->initMigration->rollback();
        
        $this->setExpectedException('Phoenix\Exception\DatabaseQueryExecuteException', 'SQLSTATE[HY000]: no such table: phoenix_log. Query SELECT * FROM "phoenix_log"; fails');
        $this->manager->executedMigrations();
    }
    
    public function testWrongType()
    {
        $this->setExpectedException('\Phoenix\Exception\InvalidArgumentValueException', 'Type "type" is not allowed.');
        $this->manager->findMigrationsToExecute('type');
    }
    
    public function testWrongTarget()
    {
        $this->setExpectedException('\Phoenix\Exception\InvalidArgumentValueException', 'Target "target" is not allowed.');
        $this->manager->findMigrationsToExecute('up', 'target');
    }
    
    private function checkMigrations($migrations, $count)
    {
        $this->assertTrue(is_array($migrations));
        $this->assertCount($count, $migrations);
        foreach ($migrations as $migration) {
            $this->assertInstanceOf('\Phoenix\Migration\AbstractMigration', $migration);
        }
    }
}
