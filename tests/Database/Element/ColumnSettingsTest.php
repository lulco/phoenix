<?php

declare(strict_types=1);

namespace Phoenix\Tests\Database\Element;

use Phoenix\Database\Element\ColumnSettings;
use Phoenix\Exception\InvalidArgumentValueException;
use PHPUnit\Framework\TestCase;

final class ColumnSettingsTest extends TestCase
{
    public function testSimple(): void
    {
        $columnSettings = new ColumnSettings();
        $this->assertEquals([], $columnSettings->getSettings());
        $this->assertFalse($columnSettings->allowNull());
        $this->assertNull($columnSettings->getDefault());
        $this->assertNull($columnSettings->getLength());
        $this->assertEquals(100, $columnSettings->getLength(100));
        $this->assertNull($columnSettings->getDecimals());
        $this->assertEquals(2, $columnSettings->getDecimals(2));
        $this->assertTrue($columnSettings->isSigned());
        $this->assertFalse($columnSettings->isAutoincrement());
        $this->assertFalse($columnSettings->isFirst());
        $this->assertNull($columnSettings->getAfter());
        $this->assertNull($columnSettings->getCharset());
        $this->assertNull($columnSettings->getCollation());
        $this->assertNull($columnSettings->getValues());
        $this->assertNull($columnSettings->getComment());
    }

    public function testComplex(): void
    {
        $columnSettings = new ColumnSettings(['null' => true, 'default' => '', 'length' => 255, 'after' => 'id']);
        $this->assertEquals(['null' => true, 'default' => '', 'length' => 255, 'after' => 'id'], $columnSettings->getSettings());
        $this->assertTrue($columnSettings->allowNull());
        $this->assertEquals('', $columnSettings->getDefault());
        $this->assertEquals(255, $columnSettings->getLength());
        $this->assertEquals(255, $columnSettings->getLength(100));
        $this->assertNull($columnSettings->getDecimals());
        $this->assertEquals(2, $columnSettings->getDecimals(2));
        $this->assertTrue($columnSettings->isSigned());
        $this->assertFalse($columnSettings->isAutoincrement());
        $this->assertFalse($columnSettings->isFirst());
        $this->assertEquals('id', $columnSettings->getAfter());
        $this->assertNull($columnSettings->getCharset());
        $this->assertNull($columnSettings->getCollation());
        $this->assertNull($columnSettings->getValues());
        $this->assertNull($columnSettings->getComment());
    }

    public function testFullSettings(): void
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

        $columnSettings = new ColumnSettings($settings);
        $this->assertEquals($settings, $columnSettings->getSettings());
        $this->assertFalse($columnSettings->allowNull());
        $this->assertEquals('default_value', $columnSettings->getDefault());
        $this->assertEquals(100, $columnSettings->getLength());
        $this->assertEquals(100, $columnSettings->getLength(150));
        $this->assertNull($columnSettings->getDecimals());
        $this->assertEquals(2, $columnSettings->getDecimals(2));
        $this->assertFalse($columnSettings->isSigned());
        $this->assertFalse($columnSettings->isAutoincrement());
        $this->assertTrue($columnSettings->isFirst());
        $this->assertNull($columnSettings->getAfter());
        $this->assertEquals('my_charset', $columnSettings->getCharset());
        $this->assertEquals('my_collation', $columnSettings->getCollation());
        $this->assertEquals(['first', 'second', 'third'], $columnSettings->getValues());
        $this->assertEquals('My comment', $columnSettings->getComment());
    }

    public function testNotAllowedSetting(): void
    {
        $this->expectException(InvalidArgumentValueException::class);
        $this->expectExceptionMessage('Setting "not_allowed_setting" is not allowed.');
        new ColumnSettings(['not_allowed_setting' => true]);
    }

    public function testNotAllowedSettingValue(): void
    {
        $this->expectException(InvalidArgumentValueException::class);
        $this->expectExceptionMessage('Value "123" is not allowed for setting "null".');
        new ColumnSettings(['null' => 123]);
    }
}
