<?php

declare(strict_types=1);

namespace Phoenix\Database\Element;

final class IndexColumnSettings
{
    public const SETTING_ORDER = 'order';
    public const SETTING_LENGTH = 'length';

    public const SETTING_ORDER_ASC = 'ASC';
    public const SETTING_ORDER_DESC = 'DESC';

    public const DEFAULT_SETTINGS = [
        self::SETTING_ORDER => self::SETTING_ORDER_ASC,
        self::SETTING_LENGTH => null,
    ];

    /** @var array<string, int|string> */
    private array $settings;

    /**
     * @param array<string, int|string> $settings
     */
    public function __construct(array $settings)
    {
        if (isset($settings[self::SETTING_ORDER])) {
            /** @var string $settingOrder */
            $settingOrder = $settings[self::SETTING_ORDER];
            $settings[self::SETTING_ORDER] = strtoupper($settingOrder);
        }
        $this->settings = $settings;
    }

    /**
     * @return array<string, int|string>
     */
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
