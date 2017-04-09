<?php

namespace Phoenix\Tests\Database\Element;

use Phoenix\Database\Element\Index;
use Phoenix\Exception\InvalidArgumentValueException;
use PHPUnit_Framework_TestCase;

class IndexTest extends PHPUnit_Framework_TestCase
{
    public function testSimple()
    {
        $index = new Index('title', 'title', Index::TYPE_NORMAL, Index::METHOD_BTREE);
        $this->assertEquals('title', $index->getName());
        $this->assertCount(1, $index->getColumns());
        $this->assertEquals('', $index->getType());
        $this->assertEquals('BTREE', $index->getMethod());
    }

    public function testArray()
    {
        $index = new Index(['title', 'alias'], 'title_alias');
        $this->assertEquals('title_alias', $index->getName());
        $this->assertCount(2, $index->getColumns());
        $this->assertEquals('', $index->getType());
        $this->assertEquals('', $index->getMethod());
    }

    public function testUnique()
    {
        $index = new Index('title', 'title', Index::TYPE_UNIQUE, Index::METHOD_HASH);
        $this->assertEquals('title', $index->getName());
        $this->assertCount(1, $index->getColumns());
        $this->assertEquals('UNIQUE', $index->getType());
        $this->assertEquals('HASH', $index->getMethod());

        $index = new Index('title', 'title', 'UNIQUE');
        $this->assertEquals('title', $index->getName());
        $this->assertCount(1, $index->getColumns());
        $this->assertEquals('UNIQUE', $index->getType());

        $index = new Index('title', 'title', 'unique');
        $this->assertEquals('title', $index->getName());
        $this->assertCount(1, $index->getColumns());
        $this->assertEquals('UNIQUE', $index->getType());
    }

    public function testFulltext()
    {
        $index = new Index(['title', 'alias'], 'title_alias', Index::TYPE_FULLTEXT, 'hash');
        $this->assertEquals('title_alias', $index->getName());
        $this->assertCount(2, $index->getColumns());
        $this->assertEquals('FULLTEXT', $index->getType());
        $this->assertEquals('HASH', $index->getMethod());

        $index = new Index(['title'], 'title', 'FULLTEXT');
        $this->assertEquals('title', $index->getName());
        $this->assertCount(1, $index->getColumns());
        $this->assertEquals('FULLTEXT', $index->getType());

        $index = new Index(['alias', 'title'], 'alias_title', 'fulltext');
        $this->assertEquals('alias_title', $index->getName());
        $this->assertCount(2, $index->getColumns());
        $this->assertEquals('FULLTEXT', $index->getType());
    }

    public function testUnknownType()
    {
        $this->expectException(InvalidArgumentValueException::class);
        $this->expectExceptionMessage('Index type "unknown" is not allowed');
        new Index('title', 'title', 'unknown');
    }

    public function testUnknownMethod()
    {
        $this->expectException(InvalidArgumentValueException::class);
        $this->expectExceptionMessage('Index method "unknown" is not allowed');
        new Index('title', 'title', '', 'unknown');
    }
}
