<?php

namespace Phoenix\Tests\Comparator;

use Phoenix\Comparator\SettingsComparator;
use Phoenix\Database\Element\ColumnSettings;
use PHPUnit\Framework\TestCase;

class SettingsComparatorTest extends TestCase
{
    public function testSameEmpty()
    {
        $settings1 = new ColumnSettings();
        $settings2 = new ColumnSettings();
        $settingsComparator = new SettingsComparator();
        $this->assertEquals([], $settingsComparator->diff($settings1, $settings2));
    }

    public function testSameNonEmpty()
    {
        $settings1 = new ColumnSettings(['null' => true, 'default' => 10]);
        $settings2 = new ColumnSettings(['null' => true, 'default' => 10]);
        $settingsComparator = new SettingsComparator();
        $this->assertEquals([], $settingsComparator->diff($settings1, $settings2));
    }

    public function testSameEnumValues()
    {
        $settings1 = new ColumnSettings(['values' => ['a', 'b', 'c']]);
        $settings2 = new ColumnSettings(['values' => ['a', 'b', 'c']]);
        $settingsComparator = new SettingsComparator();
        $this->assertEquals([], $settingsComparator->diff($settings1, $settings2));
    }

    public function testSomeDifferentEnumValues()
    {
        $settings1 = new ColumnSettings(['values' => ['a', 'b', 'c']]);
        $settings2 = new ColumnSettings(['values' => ['b', 'c', 'd']]);
        $settingsComparator = new SettingsComparator();
        $this->assertEquals(['values' => ['b', 'c', 'd']], $settingsComparator->diff($settings1, $settings2));
    }

    public function testCompletelyDifferentEnumValues()
    {
        $settings1 = new ColumnSettings(['values' => ['a', 'b', 'c']]);
        $settings2 = new ColumnSettings(['values' => ['d', 'e', 'f']]);
        $settingsComparator = new SettingsComparator();
        $this->assertEquals(['values' => ['d', 'e', 'f']], $settingsComparator->diff($settings1, $settings2));
    }
}
