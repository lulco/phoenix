<?php

namespace Phoenix\Tests;

use Phoenix\QueryBuilder\Index;
use PHPUnit_Framework_TestCase;

class IndexTest extends PHPUnit_Framework_TestCase
{
    public function testSimple()
    {
        $index = new Index('title');
        $this->assertEquals('title', $index->getName());
        $this->assertCount(1, $index->getColumns());
        $this->assertEquals('INDEX', $index->getType());
    }
    
    public function testArray()
    {
        $index = new Index(['title', 'alias']);
        $this->assertEquals('title_alias', $index->getName());
        $this->assertCount(2, $index->getColumns());
        $this->assertEquals('INDEX', $index->getType());
    }
    
    public function testUnique()
    {
        $index = new Index('title', Index::TYPE_UNIQUE);
        $this->assertEquals('title', $index->getName());
        $this->assertCount(1, $index->getColumns());
        $this->assertEquals('UNIQUE INDEX', $index->getType());
        
        $index = new Index('title', 'UNIQUE');
        $this->assertEquals('title', $index->getName());
        $this->assertCount(1, $index->getColumns());
        $this->assertEquals('UNIQUE INDEX', $index->getType());
        
        $index = new Index('title', 'unique');
        $this->assertEquals('title', $index->getName());
        $this->assertCount(1, $index->getColumns());
        $this->assertEquals('UNIQUE INDEX', $index->getType());
    }
    
    public function testFulltext()
    {
        $index = new Index(['title', 'alias'], Index::TYPE_FULLTEXT);
        $this->assertEquals('title_alias', $index->getName());
        $this->assertCount(2, $index->getColumns());
        $this->assertEquals('FULLTEXT INDEX', $index->getType());
        
        $index = new Index(['title'], 'FULLTEXT');
        $this->assertEquals('title', $index->getName());
        $this->assertCount(1, $index->getColumns());
        $this->assertEquals('FULLTEXT INDEX', $index->getType());
        
        $index = new Index(['alias', 'title'], 'fulltext');
        $this->assertEquals('alias_title', $index->getName());
        $this->assertCount(2, $index->getColumns());
        $this->assertEquals('FULLTEXT INDEX', $index->getType());
    }
    
    public function testUnknownType()
    {
        $this->setExpectedException('\InvalidArgumentException', 'Index type "unknown" is not allowed');
        $index = new Index('title', 'unknown');
    }
}
