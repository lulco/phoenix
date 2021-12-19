<?php

declare(strict_types=1);

namespace Phoenix\Comparator;

use Phoenix\Database\Element\ColumnSettings;

final class SettingsComparator
{
    /**
     * @return array<string, mixed>
     */
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

    /**
     * @param array<string, mixed> $array1
     * @param array<string, mixed> $array2
     * @return array<string, mixed>
     */
    private function arrayRecursiveDiff(array $array1, array $array2): array
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
                continue;
            }
            if ($value !== $array2[$key]) {
                $return[$key] = $value;
            }
        }
        return $return;
    }
}
