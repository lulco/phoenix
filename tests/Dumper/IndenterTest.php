<?php

declare(strict_types=1);

namespace Phoenix\Tests\Dumper;

use Phoenix\Dumper\Indenter;
use PHPUnit\Framework\TestCase;

final class IndenterTest extends TestCase
{
    public function testDefault(): void
    {
        $indenter = new Indenter();
        $this->assertEquals('    ', $indenter->indent());
    }

    public function testTwoSpaces(): void
    {
        $indenter = new Indenter();
        $this->assertEquals('  ', $indenter->indent('2spaces'));
        $this->assertEquals('  ', $indenter->indent('2 spaces'));
        $this->assertEquals('  ', $indenter->indent('2-spaces'));
        $this->assertEquals('  ', $indenter->indent('2_spaces'));
    }

    public function testThreeSpaces(): void
    {
        $indenter = new Indenter();
        $this->assertEquals('   ', $indenter->indent('3spaces'));
        $this->assertEquals('   ', $indenter->indent('3 spaces'));
        $this->assertEquals('   ', $indenter->indent('3-spaces'));
        $this->assertEquals('   ', $indenter->indent('3_spaces'));
    }

    public function testFourSpaces(): void
    {
        $indenter = new Indenter();
        $this->assertEquals('    ', $indenter->indent('4spaces'));
        $this->assertEquals('    ', $indenter->indent('4 spaces'));
        $this->assertEquals('    ', $indenter->indent('4-spaces'));
        $this->assertEquals('    ', $indenter->indent('4_spaces'));
    }

    public function testFiveSpaces(): void
    {
        $indenter = new Indenter();
        $this->assertEquals('     ', $indenter->indent('5spaces'));
        $this->assertEquals('     ', $indenter->indent('5 spaces'));
        $this->assertEquals('     ', $indenter->indent('5-spaces'));
        $this->assertEquals('     ', $indenter->indent('5_spaces'));
    }

    public function testSixSpaces(): void
    {
        // this is not available and default is used
        $indenter = new Indenter();
        $this->assertEquals('    ', $indenter->indent('6spaces'));
        $this->assertEquals('    ', $indenter->indent('6 spaces'));
        $this->assertEquals('    ', $indenter->indent('6-spaces'));
        $this->assertEquals('    ', $indenter->indent('6_spaces'));
    }

    public function testTab(): void
    {
        $indenter = new Indenter();
        $this->assertEquals("\t", $indenter->indent('tab'));
    }

    public function testUnknown(): void
    {
        $indenter = new Indenter();
        $this->assertEquals('    ', $indenter->indent('unknown'));
    }
}
