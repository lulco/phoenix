<?php

namespace Phoenix\Tests;

use Phoenix\QueryBuilder\ForeignKey;
use PHPUnit_Framework_TestCase;

class ForeignKeyTest extends PHPUnit_Framework_TestCase
{
    public function testSimple()
    {
        $foreignKey = new ForeignKey('title', 'ref_table');
        $this->assertEquals('title', $foreignKey->getName());
        $this->assertCount(1, $foreignKey->getColumns());
        $this->assertEquals(['title'], $foreignKey->getColumns());
        $this->assertEquals('ref_table', $foreignKey->getReferencedTable());
        $this->assertCount(1, $foreignKey->getReferencedColumns());
        $this->assertEquals(['id'], $foreignKey->getReferencedColumns());
        $this->assertEquals('RESTRICT', $foreignKey->getOnDelete());
        $this->assertEquals('RESTRICT', $foreignKey->getOnUpdate());
    }
    
    public function testArray()
    {
        $foreignKey = new ForeignKey(['title', 'alias'], 'ref_table', ['t', 'a']);
        $this->assertEquals('title_alias', $foreignKey->getName());
        $this->assertCount(2, $foreignKey->getColumns());
        $this->assertEquals(['title', 'alias'], $foreignKey->getColumns());
        $this->assertEquals('ref_table', $foreignKey->getReferencedTable());
        $this->assertCount(2, $foreignKey->getReferencedColumns());
        $this->assertEquals(['t', 'a'], $foreignKey->getReferencedColumns());
        $this->assertEquals('RESTRICT', $foreignKey->getOnDelete());
        $this->assertEquals('RESTRICT', $foreignKey->getOnUpdate());
    }
    
    public function testSetNull()
    {
        $foreignKey = new ForeignKey('foreign_key_id', 'foreign_table', 'id', 'set null', 'SET NULL');
        $this->assertEquals('foreign_key_id', $foreignKey->getName());
        $this->assertCount(1, $foreignKey->getColumns());
        $this->assertEquals(['foreign_key_id'], $foreignKey->getColumns());
        $this->assertEquals('foreign_table', $foreignKey->getReferencedTable());
        $this->assertCount(1, $foreignKey->getReferencedColumns());
        $this->assertEquals(['id'], $foreignKey->getReferencedColumns());
        $this->assertEquals('SET NULL', $foreignKey->getOnDelete());
        $this->assertEquals('SET NULL', $foreignKey->getOnUpdate());
    }
    
    public function testCascadeAndNoAction()
    {
        $foreignKey = new ForeignKey('foreign_key_id', 'foreign_table', 'id', 'Cascade', 'No Action');
        $this->assertEquals('foreign_key_id', $foreignKey->getName());
        $this->assertCount(1, $foreignKey->getColumns());
        $this->assertEquals(['foreign_key_id'], $foreignKey->getColumns());
        $this->assertEquals('foreign_table', $foreignKey->getReferencedTable());
        $this->assertCount(1, $foreignKey->getReferencedColumns());
        $this->assertEquals(['id'], $foreignKey->getReferencedColumns());
        $this->assertEquals('CASCADE', $foreignKey->getOnDelete());
        $this->assertEquals('NO ACTION', $foreignKey->getOnUpdate());
    }
    
    public function testUnknownAction()
    {
        $this->setExpectedException('\Phoenix\Exception\InvalidArgumentValueException', 'Action "unknown" is not allowed on delete');
        $foreignKey = new ForeignKey('foreign_key_id', 'foreign_table', 'id', 'unknown');
    }
    
    public function testUnknownMethod()
    {
        $this->setExpectedException('\Phoenix\Exception\InvalidArgumentValueException', 'Action "unknown" is not allowed on update');
        $foreignKey = new ForeignKey('foreign_key_id', 'foreign_table', 'id', 'restrict', 'unknown');
    }
}
