<?php

namespace Phoenix\Tests\Migration;

use Phoenix\Migration\FilesFinder;
use PHPUnit_Framework_TestCase;

class FilesFinderTest extends PHPUnit_Framework_TestCase
{
    public function testDirectories()
    {
        $finder = new FilesFinder();
        $this->assertCount(0, $finder->getDirectories());
        $this->assertInstanceOf('\Phoenix\Migration\FilesFinder', $finder->addDirectory(__DIR__ . '/../fake/structure/migration_directory_1'));
        $this->assertCount(1, $finder->getDirectories());
        
        $this->assertInstanceOf('\Phoenix\Migration\FilesFinder', $finder->addDirectory(__DIR__ . '/../fake/structure/migration_directory_2'));
        $this->assertCount(2, $finder->getDirectories());
        
        $this->assertInstanceOf('\Phoenix\Migration\FilesFinder', $finder->removeDirectory(__DIR__ . '/../fake/structure/migration_directory_1'));
        $this->assertCount(1, $finder->getDirectories());
        
        $this->assertInstanceOf('\Phoenix\Migration\FilesFinder', $finder->addDirectory(__DIR__ . '/../fake/structure/migration_directory_3'));
        $this->assertCount(2, $finder->getDirectories());
        
        // add same directory second time
        $this->assertInstanceOf('\Phoenix\Migration\FilesFinder', $finder->addDirectory(__DIR__ . '/../fake/structure/migration_directory_3'));
        $this->assertCount(2, $finder->getDirectories());
        
        $this->assertInstanceOf('\Phoenix\Migration\FilesFinder', $finder->removeDirectories());
        $this->assertCount(0, $finder->getDirectories());
    }
    
    public function testAddNotExistingDirectory()
    {
        $finder = new FilesFinder();
        $this->setExpectedException('\InvalidArgumentException', 'Directory "not_existing_directory" not found');
        $finder->addDirectory('not_existing_directory');
    }
    
    public function testAddFileAsDirectory()
    {
        $finder = new FilesFinder();
        $this->setExpectedException('\InvalidArgumentException', '"' . __DIR__ . '/../fake/structure/migration_directory_1/20150428140909_first_migration.php' . '" is not directory');
        $finder->addDirectory(__DIR__ . '/../fake/structure/migration_directory_1/20150428140909_first_migration.php');
    }
    
    public function testRemoveNotAddedDirectory()
    {
        $finder = new FilesFinder();
        $this->setExpectedException('\InvalidArgumentException');
        $finder->removeDirectory('not_added_directory');
    }
    
    public function testGetMigrationFiles()
    {
        $finder = new FilesFinder();
        $finder->addDirectory(__DIR__ . '/../fake/structure/migration_directory_1');
        $finder->addDirectory(__DIR__ . '/../fake/structure/migration_directory_2');
        $finder->addDirectory(__DIR__ . '/../fake/structure/migration_directory_3');
        
        $files = $finder->getFiles();
        $this->assertTrue(is_array($files));
        $this->assertCount(4, $files);
    }
}
