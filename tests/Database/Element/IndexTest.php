<?php

declare(strict_types=1);

namespace Phoenix\Tests\Database\Element;

use Phoenix\Database\Element\Index;
use Phoenix\Database\Element\IndexColumn;
use Phoenix\Exception\InvalidArgumentValueException;
use PHPUnit\Framework\TestCase;

final class IndexTest extends TestCase
{
    public function testSimple(): void
    {
        $index = new Index([new IndexColumn('title')], 'title', Index::TYPE_NORMAL, Index::METHOD_BTREE);
        $this->assertEquals('title', $index->getName());
        $this->assertCount(1, $index->getColumns());
        $this->assertContainsOnlyInstancesOf(IndexColumn::class, $index->getColumns());
        $this->assertEquals('', $index->getType());
        $this->assertEquals('BTREE', $index->getMethod());
    }

    public function testArray(): void
    {
        $index = new Index([new IndexColumn('title'), new IndexColumn('alias')], 'title_alias');
        $this->assertEquals('title_alias', $index->getName());
        $this->assertCount(2, $index->getColumns());
        $this->assertContainsOnlyInstancesOf(IndexColumn::class, $index->getColumns());
        $this->assertEquals('', $index->getType());
        $this->assertEquals('', $index->getMethod());
    }

    public function testUnique(): void
    {
        $index = new Index([new IndexColumn('title')], 'title', Index::TYPE_UNIQUE, Index::METHOD_HASH);
        $this->assertEquals('title', $index->getName());
        $this->assertCount(1, $index->getColumns());
        $this->assertContainsOnlyInstancesOf(IndexColumn::class, $index->getColumns());
        $this->assertEquals('UNIQUE', $index->getType());
        $this->assertEquals('HASH', $index->getMethod());

        $index = new Index([new IndexColumn('title')], 'title', 'UNIQUE');
        $this->assertEquals('title', $index->getName());
        $this->assertCount(1, $index->getColumns());
        $this->assertContainsOnlyInstancesOf(IndexColumn::class, $index->getColumns());
        $this->assertEquals('UNIQUE', $index->getType());

        $index = new Index([new IndexColumn('title')], 'title', 'unique');
        $this->assertEquals('title', $index->getName());
        $this->assertCount(1, $index->getColumns());
        $this->assertContainsOnlyInstancesOf(IndexColumn::class, $index->getColumns());
        $this->assertEquals('UNIQUE', $index->getType());
    }

    public function testFulltext(): void
    {
        $index = new Index([new IndexColumn('title'), new IndexColumn('title')], 'title_alias', Index::TYPE_FULLTEXT, 'hash');
        $this->assertEquals('title_alias', $index->getName());
        $this->assertCount(2, $index->getColumns());
        $this->assertContainsOnlyInstancesOf(IndexColumn::class, $index->getColumns());
        $this->assertEquals('FULLTEXT', $index->getType());
        $this->assertEquals('HASH', $index->getMethod());

        $index = new Index([new IndexColumn('title')], 'title', 'FULLTEXT');
        $this->assertEquals('title', $index->getName());
        $this->assertCount(1, $index->getColumns());
        $this->assertContainsOnlyInstancesOf(IndexColumn::class, $index->getColumns());
        $this->assertEquals('FULLTEXT', $index->getType());

        $index = new Index([new IndexColumn('alias'), new IndexColumn('title')], 'alias_title', 'fulltext');
        $this->assertEquals('alias_title', $index->getName());
        $this->assertCount(2, $index->getColumns());
        $this->assertContainsOnlyInstancesOf(IndexColumn::class, $index->getColumns());
        $this->assertEquals('FULLTEXT', $index->getType());
    }

    public function testUnknownType(): void
    {
        $this->expectException(InvalidArgumentValueException::class);
        $this->expectExceptionMessage('Index type "unknown" is not allowed');
        new Index([new IndexColumn('title')], 'title', 'unknown');
    }

    public function testUnknownMethod(): void
    {
        $this->expectException(InvalidArgumentValueException::class);
        $this->expectExceptionMessage('Index method "unknown" is not allowed');
        new Index([new IndexColumn('title')], 'title', '', 'unknown');
    }
}
