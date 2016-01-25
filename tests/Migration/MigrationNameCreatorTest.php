<?php

namespace Phoenix\Tests;

use Phoenix\Migration\MigrationNameCreator;
use PHPUnit_Framework_TestCase;

class MigrationNameCreatorTest extends PHPUnit_Framework_TestCase
{
    public function testCreateMigrationName()
    {
        $className = 'AddSomethingToTable';
        $migrationNameCreator = new MigrationNameCreator($className);
        $this->assertEquals(date('YmdHis') . '_add_something_to_table.php', $migrationNameCreator->getFileName());
        $this->assertEquals('AddSomethingToTable', $migrationNameCreator->getClassName());
        $this->assertEquals('', $migrationNameCreator->getNamespace());
        
        $className = '\AddSomethingToTable';
        $migrationNameCreator = new MigrationNameCreator($className);
        $this->assertEquals(date('YmdHis') . '_add_something_to_table.php', $migrationNameCreator->getFileName());
        $this->assertEquals('AddSomethingToTable', $migrationNameCreator->getClassName());
        $this->assertEquals('', $migrationNameCreator->getNamespace());
        
        $className = 'MyNamespace\AddSomethingToTable';
        $migrationNameCreator = new MigrationNameCreator($className);
        $this->assertEquals(date('YmdHis') . '_add_something_to_table.php', $migrationNameCreator->getFileName());
        $this->assertEquals('AddSomethingToTable', $migrationNameCreator->getClassName());
        $this->assertEquals('MyNamespace', $migrationNameCreator->getNamespace());
        
        $className = '\MyNamespace\SecondLevel\AddSomethingToTable';
        $migrationNameCreator = new MigrationNameCreator($className);
        $this->assertEquals(date('YmdHis') . '_add_something_to_table.php', $migrationNameCreator->getFileName());
        $this->assertEquals('AddSomethingToTable', $migrationNameCreator->getClassName());
        $this->assertEquals('MyNamespace\SecondLevel', $migrationNameCreator->getNamespace());
    }
}
