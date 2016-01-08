<?php

namespace Phoenix\Tests;

use Phoenix\Migration\ClassNameCreator;
use Phoenix\Migration\FilesFinder;
use Phoenix\Migration\Runner;
use Phoenix\Tests\Database\Adapter\DummyMysqlAdapter;
use PHPUnit_Framework_TestCase;

class RunnerTest extends PHPUnit_Framework_TestCase
{
    public function testUp()
    {
        $adapter = new DummyMysqlAdapter();
        $runner = new Runner();
        
        $file = __DIR__ . '/../fake/structure/migration_directory_1/20150428140909_first_migration.php';
        require_once $file;
        $classNameCreator = new ClassNameCreator($file);
        $className = $classNameCreator->getClassName();
        $runner->addMigration(new $className($adapter));
        
        $file = __DIR__ . '/../fake/structure/migration_directory_1/20150518091732_second_change_of_something.php';
        require_once $file;
        $classNameCreator = new ClassNameCreator($file);
        $className = $classNameCreator->getClassName();
        $runner->addMigration(new $className($adapter));
        
        $file = __DIR__ . '/../fake/structure/migration_directory_3/20150709132012_third.php';
        require_once $file;
        $classNameCreator = new ClassNameCreator($file);
        $className = $classNameCreator->getClassName();
        $runner->addMigration(new $className($adapter));
        
        $file = __DIR__ . '/../fake/structure/migration_directory_2/20150921111111_fourth_add.php';
        require_once $file;
        $classNameCreator = new ClassNameCreator($file);
        $className = $classNameCreator->getClassName();
        $runner->addMigration(new $className($adapter));
        
        $this->assertEquals(4, $runner->up());
    }
    
    public function testDown()
    {
        $adapter = new DummyMysqlAdapter();
        $runner = new Runner();
        
        $file = __DIR__ . '/../fake/structure/migration_directory_2/20150921111111_fourth_add.php';
        require_once $file;
        $classNameCreator = new ClassNameCreator($file);
        $className = $classNameCreator->getClassName();
        $runner->addMigration(new $className($adapter));
        
        $this->assertEquals(1, $runner->down());
    }
    
    public function testSs()
    {
        $filesFinder = new FilesFinder();
        $filesFinder->addDirectory(__DIR__ . '/../fake/structure/migration_directory_1');
        $filesFinder->addDirectory(__DIR__ . '/../fake/structure/migration_directory_2');
        $filesFinder->addDirectory(__DIR__ . '/../fake/structure/migration_directory_3');

        $adapter = new DummyMysqlAdapter();
        
        $migrations = [];
        foreach ($filesFinder->getFiles() as $file) {
            require_once $file;
            $classNameCreator = new ClassNameCreator($file);
            $className = $classNameCreator->getClassName();
            $migrations[$classNameCreator->getDatetime() . '|' . $className] = new $className($adapter);
        }

        ksort($migrations);
        $runner = new Runner($adapter);
        foreach ($migrations as $migration) {
            $runner->addMigration($migration);
        }
        $runner->up();
        $runner->down();
        $runner->up();
    }
}
