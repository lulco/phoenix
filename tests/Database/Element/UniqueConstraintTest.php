<?php

declare(strict_types=1);

namespace Phoenix\Tests\Database\Element;

use Phoenix\Database\Element\UniqueConstraint;
use PHPUnit\Framework\TestCase;

final class UniqueConstraintTest extends TestCase
{
    public function testSimple(): void
    {
        $uniqueConstraint = new UniqueConstraint(['sku'], 'u_sku');
        $this->assertEquals('u_sku', $uniqueConstraint->getName());
        $this->assertCount(1, $uniqueConstraint->getColumns());
        $this->assertEquals(['sku'], $uniqueConstraint->getColumns());
    }

    public function testArray(): void
    {
        $uniqueConstraint = new UniqueConstraint(['sku', 'alias'], 'u_sku');
        $this->assertEquals('u_sku', $uniqueConstraint->getName());
        $this->assertCount(2, $uniqueConstraint->getColumns());
        $this->assertEquals(['sku', 'alias'], $uniqueConstraint->getColumns());
    }
}
