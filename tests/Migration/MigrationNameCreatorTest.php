<?php

declare(strict_types=1);

namespace Phoenix\Tests\Migration;

use Phoenix\Migration\MigrationNameCreator;
use PHPUnit\Framework\TestCase;

final class MigrationNameCreatorTest extends TestCase
{
    public function testCreateMigrationName(): void
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
