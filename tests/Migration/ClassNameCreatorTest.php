<?php

namespace Phoenix\Tests;

use Phoenix\Migration\ClassNameCreator;
use PHPUnit_Framework_TestCase;

class ClassNameCreatorTest extends PHPUnit_Framework_TestCase
{
    public function testClassName()
    {
        $filepath = __DIR__ . '/../fake/structure/migration_directory_1/20150428140909_first_migration.php';
        $creator = new ClassNameCreator($filepath);
        $this->assertEquals('\FirstMigration', $creator->getClassName());
        $this->assertEquals('20150428140909', $creator->getDatetime());
        
        $filepath = __DIR__ . '/../fake/structure/migration_directory_1/20150518091732_second_change_of_something.php';
        $creator = new ClassNameCreator($filepath);
        $this->assertEquals('\SecondChangeOfSomething', $creator->getClassName());
        $this->assertEquals('20150518091732', $creator->getDatetime());
        
        $filepath = __DIR__ . '/../fake/structure/migration_directory_3/20150709132012_third.php';
        $creator = new ClassNameCreator($filepath);
        $this->assertEquals('\Phoenix\Tests\Fake\Structure\Third', $creator->getClassName());
        $this->assertEquals('20150709132012', $creator->getDatetime());
        
        $filepath = __DIR__ . '/../fake/structure/migration_directory_2/20150921111111_fourth_add.php';
        $creator = new ClassNameCreator($filepath);
        $this->assertEquals('\FourthAdd', $creator->getClassName());
        $this->assertEquals('20150921111111', $creator->getDatetime());
    }
}
