<?php

namespace Phoenix\Tests\Dumper;

use Dumper\Indenter;

class IndenterTest extends \PHPUnit_Framework_TestCase
{
    public function testDefault()
    {
        $indenter = new Indenter();
        $this->assertEquals('    ', $indenter->indent());
    }

    public function testTwoSpaces()
    {
        $indenter = new Indenter();
        $this->assertEquals('  ', $indenter->indent('2spaces'));
        $this->assertEquals('  ', $indenter->indent('2 spaces'));
        $this->assertEquals('  ', $indenter->indent('2-spaces'));
        $this->assertEquals('  ', $indenter->indent('2_spaces'));
    }

    public function testThreeSpaces()
    {
        $indenter = new Indenter();
        $this->assertEquals('   ', $indenter->indent('3spaces'));
        $this->assertEquals('   ', $indenter->indent('3 spaces'));
        $this->assertEquals('   ', $indenter->indent('3-spaces'));
        $this->assertEquals('   ', $indenter->indent('3_spaces'));
    }

    public function testFourSpaces()
    {
        $indenter = new Indenter();
        $this->assertEquals('    ', $indenter->indent('4spaces'));
        $this->assertEquals('    ', $indenter->indent('4 spaces'));
        $this->assertEquals('    ', $indenter->indent('4-spaces'));
        $this->assertEquals('    ', $indenter->indent('4_spaces'));
    }

    public function testFiveSpaces()
    {
        $indenter = new Indenter();
        $this->assertEquals('     ', $indenter->indent('5spaces'));
        $this->assertEquals('     ', $indenter->indent('5 spaces'));
        $this->assertEquals('     ', $indenter->indent('5-spaces'));
        $this->assertEquals('     ', $indenter->indent('5_spaces'));
    }

    public function testSixSpaces()
    {
        // this is not available and default is used
        $indenter = new Indenter();
        $this->assertEquals('    ', $indenter->indent('6spaces'));
        $this->assertEquals('    ', $indenter->indent('6 spaces'));
        $this->assertEquals('    ', $indenter->indent('6-spaces'));
        $this->assertEquals('    ', $indenter->indent('6_spaces'));
    }

    public function testTab()
    {
        $indenter = new Indenter();
        $this->assertEquals("\t", $indenter->indent('tab'));
    }

    public function testUnknown()
    {
        $indenter = new Indenter();
        $this->assertEquals('    ', $indenter->indent('unknown'));
    }
}
