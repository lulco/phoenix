<?php

namespace Phoenix\Tests\Database\Element;

use Phoenix\Database\Element\Column;
use PHPUnit_Framework_TestCase;

class ColumnTest extends PHPUnit_Framework_TestCase
{
    public function testSimple()
    {
        $column = new Column('title', 'string');
        $this->assertEquals('title', $column->getName());
        $this->assertEquals('string', $column->getType());
        $this->assertFalse($column->allowNull());
        $this->assertNull($column->getDefault());
        $this->assertNull($column->getLength());
        $this->assertEquals(100, $column->getLength(100));
        $this->assertNull($column->getDecimals());
        $this->assertEquals(2, $column->getDecimals(2));
        $this->assertTrue($column->isSigned());
        $this->assertFalse($column->isAutoincrement());
        $this->assertFalse($column->isFirst());
        $this->assertNull($column->getAfter());
    }
    
    public function testComplex()
    {
        $column = new Column('title', 'string', ['null' => true, 'default' => '', 'length' => 255, 'after' => 'id']);
        $this->assertEquals('title', $column->getName());
        $this->assertEquals('string', $column->getType());
        $this->assertTrue($column->allowNull());
        $this->assertEquals('', $column->getDefault());
        $this->assertEquals(255, $column->getLength());
        $this->assertEquals(255, $column->getLength(100));
        $this->assertNull($column->getDecimals());
        $this->assertEquals(2, $column->getDecimals(2));
        $this->assertTrue($column->isSigned());
        $this->assertFalse($column->isAutoincrement());
        $this->assertFalse($column->isFirst());
        $this->assertEquals('id', $column->getAfter());
    }
    
    public function testNotAllowedSetting()
    {
        $this->setExpectedException('\Phoenix\Exception\InvalidArgumentValueException', 'Setting "not_allowed_setting" is not allowed.');
        new Column('title', 'string', ['not_allowed_setting' => true]);
    }
    
    public function testNotAllowedSettingValue()
    {
        $this->setExpectedException('\Phoenix\Exception\InvalidArgumentValueException', 'Value "123" is not allowed for setting "null".');
        new Column('title', 'string', ['null' => 123]);
    }
}
