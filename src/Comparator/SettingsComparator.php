<?php

namespace Comparator;

use Phoenix\Database\Element\ColumnSettings;

class SettingsComparator
{
    public function diff(ColumnSettings $sourceColumnSettings, ColumnSettings $targetColumnSettings): array
    {
        $sourceSettings = $sourceColumnSettings->getSettings();
        $targetSettings = $targetColumnSettings->getSettings();

        if (empty(array_diff($sourceSettings, $targetSettings)) && empty(array_diff($targetSettings, $sourceSettings))) {
            return [];
        }

        // for now, actually we always have to return target settings if there is some diff, because in change migration we always need to set all settings not just diff
        // in the future it is planned to fill only those settings that we would like to change
        return $targetColumnSettings->getSettings();
    }
}
