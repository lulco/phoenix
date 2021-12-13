<?php

namespace Phoenix\Database\Element;

use Phoenix\Exception\InvalidArgumentValueException;
use ReflectionClass;

class ColumnSettings
{
    const SETTING_NULL = 'null';
    const SETTING_DEFAULT = 'default';
    const SETTING_LENGTH = 'length';
    const SETTING_DECIMALS = 'decimals';
    const SETTING_SIGNED = 'signed';
    const SETTING_AUTOINCREMENT = 'autoincrement';
    const SETTING_AFTER = 'after';
    const SETTING_FIRST = 'first';
    const SETTING_CHARSET = 'charset';
    const SETTING_COLLATION = 'collation';
    const SETTING_VALUES = 'values';
    const SETTING_COMMENT = 'comment';

    /** @var array<string, array<callable>> */
    private $allowedSettingsValues = [
        self::SETTING_NULL => ['is_bool'],
        self::SETTING_DEFAULT => ['is_null', 'is_numeric', 'is_string', 'is_bool'],
        self::SETTING_LENGTH => ['is_null', 'is_int'],
        self::SETTING_DECIMALS => ['is_null', 'is_int'],
        self::SETTING_SIGNED => ['is_bool'],
        self::SETTING_AUTOINCREMENT => ['is_bool'],
        self::SETTING_AFTER => ['is_null', 'is_string'],
        self::SETTING_FIRST => ['is_bool'],
        self::SETTING_CHARSET => ['is_null', 'is_string'],
        self::SETTING_COLLATION => ['is_null', 'is_string'],
        self::SETTING_VALUES => ['is_null', 'is_array'],
        self::SETTING_COMMENT => ['is_null', 'is_string'],
    ];

    const DEFAULT_VALUE_CURRENT_TIMESTAMP = 'CURRENT_TIMESTAMP';

    /** @var array{null?: bool, default?: mixed, length?: int, decimals?: int, signed?: bool, autoincrement?: bool, after?: string, first?: bool, charset?: string, collation?: string, values?: array<int|string, int|string>, comment?: string} */
    private $settings;

    /**
     * @param array{null?: bool, default?: mixed, length?: int, decimals?: int, signed?: bool, autoincrement?: bool, after?: string, first?: bool, charset?: string, collation?: string, values?: array<int|string, int|string>, comment?: string} $settings - list of settings
     * @throws InvalidArgumentValueException if setting is not allowed
     */
    public function __construct(array $settings = [])
    {
        $this->checkSettings($settings);
        $this->settings = $settings;
    }

    /**
     * @return array{null?: bool, default?: mixed, length?: int, decimals?: int, signed?: bool, autoincrement?: bool, after?: string, first?: bool, charset?: string, collation?: string, values?: array<int|string, int|string>, comment?: string}
     */
    public function getSettings(): array
    {
        return $this->settings;
    }

    public function allowNull(): bool
    {
        return $this->settings[self::SETTING_NULL] ?? false;
    }

    /**
     * @return mixed
     */
    public function getDefault()
    {
        return $this->settings[self::SETTING_DEFAULT] ?? null;
    }

    public function isSigned(): bool
    {
        return $this->settings[self::SETTING_SIGNED] ?? true;
    }

    public function getLength(?int $default = null): ?int
    {
        return $this->settings[self::SETTING_LENGTH] ?? $default;
    }

    public function getDecimals(?int $default = null): ?int
    {
        return $this->settings[self::SETTING_DECIMALS] ?? $default;
    }

    public function isAutoincrement(): bool
    {
        return $this->settings[self::SETTING_AUTOINCREMENT] ?? false;
    }

    public function getAfter(): ?string
    {
        return $this->settings[self::SETTING_AFTER] ?? null;
    }

    public function isFirst(): bool
    {
        return $this->settings[self::SETTING_FIRST] ?? false;
    }

    public function getCharset(): ?string
    {
        return $this->settings[self::SETTING_CHARSET] ?? null;
    }

    public function getCollation(): ?string
    {
        return $this->settings[self::SETTING_COLLATION] ?? null;
    }

    /**
     * @return mixed[]|null
     */
    public function getValues(): ?array
    {
        return $this->settings[self::SETTING_VALUES] ?? null;
    }

    public function getComment(): ?string
    {
        return $this->settings[self::SETTING_COMMENT] ?? null;
    }

    /**
     * @param array<string, mixed> $settings
     * @throws InvalidArgumentValueException
     */
    private function checkSettings(array $settings): void
    {
        $errors = [];
        $reflectionClass = new ReflectionClass($this);
        $settingsConstants = $reflectionClass->getConstants();
        foreach ($settings as $setting => $value) {
            if (!in_array($setting, $settingsConstants, true)) {
                $errors[] = 'Setting "' . $setting . '" is not allowed.';
            }
            $checkedValue = $this->getValueError($setting, $value);
            if ($checkedValue !== null) {
                $errors[] = $checkedValue;
            }
        }

        if (!empty($errors)) {
            throw new InvalidArgumentValueException(implode("\n", $errors));
        }
    }

    /**
     * @param string $setting
     * @param mixed $value
     * @return string|null
     */
    private function getValueError(string $setting, $value): ?string
    {
        if (!isset($this->allowedSettingsValues[$setting])) {
            return null;
        }

        foreach ($this->allowedSettingsValues[$setting] as $checkFunction) {
            if (call_user_func($checkFunction, $value) === true) {
                return null;
            }
        }
        return 'Value "' . $value . '" is not allowed for setting "' . $setting . '".';
    }
}
