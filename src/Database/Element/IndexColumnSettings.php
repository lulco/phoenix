<?php

namespace Phoenix\Database\Element;

class IndexColumnSettings
{
    const SETTING_ORDER = 'order';
    const SETTING_LENGTH = 'length';

    const SETTING_ORDER_ASC = 'ASC';
    const SETTING_ORDER_DESC = 'DESC';

    const DEFAULT_SETTINGS = [
        self::SETTING_ORDER => self::SETTING_ORDER_ASC,
        self::SETTING_LENGTH => null,
    ];

    /** @var array */
    private $settings;

    public function __construct(array $settings)
    {
        if (isset($settings[self::SETTING_ORDER])) {
            $settings[self::SETTING_ORDER] = strtoupper($settings[self::SETTING_ORDER]);
        }
        $this->settings = $settings;
    }

    public function getNonDefaultSettings(): array
    {
        $settings = $this->settings;
        foreach (self::DEFAULT_SETTINGS as $setting => $defaultValue) {
            if (!isset($settings[$setting])) {
                continue;
            }
            if ($settings[$setting] === $defaultValue) {
                unset($settings[$setting]);
            }
        }
        return $settings;
    }
}
