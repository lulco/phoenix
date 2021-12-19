<?php

declare(strict_types=1);

namespace Phoenix\Comparator;

use Phoenix\Database\Element\Column;

final class ColumnComparator
{
    public function diff(Column $sourceColumn, Column $targetColumn): ?Column
    {
        $sourceName = $sourceColumn->getName();
        $targetName = $targetColumn->getName();

        $sourceType = $sourceColumn->getType();
        $targetType = $targetColumn->getType();

        $settingsComparator = new SettingsComparator();
        $settings = $settingsComparator->diff($sourceColumn->getSettings(), $targetColumn->getSettings());

        if ($sourceName === $targetName && $sourceType === $targetType && empty($settings)) {
            return null;
        }

        return new Column($targetName, $targetType, $settings);
    }
}
