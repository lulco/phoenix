<?php

namespace Phoenix\Tests\Database\Element;

use Phoenix\Database\Element\Column;
use Phoenix\Database\Element\ColumnSettings;
use Phoenix\Exception\InvalidArgumentValueException;
use PHPUnit\Framework\TestCase;

class ColumnTest extends TestCase
{
    public function testSimple()
    {
        $column = new Column('title', 'string');
        $this->assertEquals('title', $column->getName());
        $this->assertEquals('string', $column->getType());
        $this->assertInstanceOf(ColumnSettings::class, $column->getSettings());
        $this->assertEquals([], $column->getSettings()->getSettings());
        $this->assertFalse($column->getSettings()->allowNull());
        $this->assertNull($column->getSettings()->getDefault());
        $this->assertNull($column->getSettings()->getLength());
        $this->assertEquals(100, $column->getSettings()->getLength(100));
        $this->assertNull($column->getSettings()->getDecimals());
        $this->assertEquals(2, $column->getSettings()->getDecimals(2));
        $this->assertTrue($column->getSettings()->isSigned());
        $this->assertFalse($column->getSettings()->isAutoincrement());
        $this->assertFalse($column->getSettings()->isFirst());
        $this->assertNull($column->getSettings()->getAfter());
        $this->assertNull($column->getSettings()->getCharset());
        $this->assertNull($column->getSettings()->getCollation());
        $this->assertNull($column->getSettings()->getValues());
        $this->assertNull($column->getSettings()->getComment());
    }

    public function testComplex()
    {
        $column = new Column('title', 'string', ['null' => true, 'default' => '', 'length' => 255, 'after' => 'id']);
        $this->assertEquals('title', $column->getName());
        $this->assertEquals('string', $column->getType());
        $this->assertInstanceOf(ColumnSettings::class, $column->getSettings());
        $this->assertEquals(['null' => true, 'default' => '', 'length' => 255, 'after' => 'id'], $column->getSettings()->getSettings());
        $this->assertTrue($column->getSettings()->allowNull());
        $this->assertEquals('', $column->getSettings()->getDefault());
        $this->assertEquals(255, $column->getSettings()->getLength());
        $this->assertEquals(255, $column->getSettings()->getLength(100));
        $this->assertNull($column->getSettings()->getDecimals());
        $this->assertEquals(2, $column->getSettings()->getDecimals(2));
        $this->assertTrue($column->getSettings()->isSigned());
        $this->assertFalse($column->getSettings()->isAutoincrement());
        $this->assertFalse($column->getSettings()->isFirst());
        $this->assertEquals('id', $column->getSettings()->getAfter());
        $this->assertNull($column->getSettings()->getCharset());
        $this->assertNull($column->getSettings()->getCollation());
        $this->assertNull($column->getSettings()->getValues());
        $this->assertNull($column->getSettings()->getComment());
    }

    public function testFullSettings()
    {
        $settings = [
            'null' => false,
            'default' => 'default_value',
            'length' => 100,
            'decimals' => null,
            'signed' => false,
            'autoincrement' => false,
            'after' => null,
            'first' => true,
            'charset' => 'my_charset',
            'collation' => 'my_collation',
            'values' => ['first', 'second', 'third'],
            'comment' => 'My comment',
        ];

        $column = new Column('title', 'string', $settings);
        $this->assertEquals('title', $column->getName());
        $this->assertEquals('string', $column->getType());
        $this->assertInstanceOf(ColumnSettings::class, $column->getSettings());
        $this->assertEquals($settings, $column->getSettings()->getSettings());
        $this->assertFalse($column->getSettings()->allowNull());
        $this->assertEquals('default_value', $column->getSettings()->getDefault());
        $this->assertEquals(100, $column->getSettings()->getLength());
        $this->assertEquals(100, $column->getSettings()->getLength(150));
        $this->assertNull($column->getSettings()->getDecimals());
        $this->assertEquals(2, $column->getSettings()->getDecimals(2));
        $this->assertFalse($column->getSettings()->isSigned());
        $this->assertFalse($column->getSettings()->isAutoincrement());
        $this->assertTrue($column->getSettings()->isFirst());
        $this->assertNull($column->getSettings()->getAfter());
        $this->assertEquals('my_charset', $column->getSettings()->getCharset());
        $this->assertEquals('my_collation', $column->getSettings()->getCollation());
        $this->assertEquals(['first', 'second', 'third'], $column->getSettings()->getValues());
        $this->assertEquals('My comment', $column->getSettings()->getComment());
    }

    public function testUnsupportedColumnType()
    {
        $this->expectException(InvalidArgumentValueException::class);
        $this->expectExceptionMessage('Type "unsupported" is not allowed');
        new Column('title', 'unsupported');
    }

    public function testNotAllowedSetting()
    {
        $this->expectException(InvalidArgumentValueException::class);
        $this->expectExceptionMessage('Setting "not_allowed_setting" is not allowed.');
        new Column('title', 'string', ['not_allowed_setting' => true]);
    }

    public function testNotAllowedSettingValue()
    {
        $this->expectException(InvalidArgumentValueException::class);
        $this->expectExceptionMessage('Value "123" is not allowed for setting "null".');
        new Column('title', 'string', ['null' => 123]);
    }
}
