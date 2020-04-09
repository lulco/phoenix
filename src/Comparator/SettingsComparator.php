<?php

namespace Comparator;

use Phoenix\Database\Element\ColumnSettings;

class SettingsComparator
{
    public function diff(ColumnSettings $sourceColumnSettings, ColumnSettings $targetColumnSettings): array
    {
        $sourceSettings = $sourceColumnSettings->getSettings();
        $targetSettings = $targetColumnSettings->getSettings();

        if (empty($this->arrayRecursiveDiff($sourceSettings, $targetSettings)) && empty($this->arrayRecursiveDiff($targetSettings, $sourceSettings))) {
            return [];
        }

        // for now, actually we always have to return target settings if there is some diff, because in change migration we always need to set all settings not just diff
        // in the future it is planned to fill only those settings that we would like to change
        return $targetColumnSettings->getSettings();
    }

    private function arrayRecursiveDiff($array1, $array2)
    {
        $return = [];
        foreach ($array1 as $key => $value) {
            if (!array_key_exists($key, $array2)) {
                $return[$key] = $value;
                continue;
            }
            if (is_array($value)) {
                $recursiveDiff = $this->arrayRecursiveDiff($value, $array2[$key]);
                if (count($recursiveDiff)) {
                    $return[$key] = $recursiveDiff;
                }
            } elseif ($value != $array2[$key]) {
                $return[$key] = $value;
            }
        }
        return $return;
    }
}
