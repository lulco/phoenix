<?php

namespace Dumper;

use Phoenix\Database\Element\Column;
use Phoenix\Database\Element\ColumnSettings;
use Phoenix\Database\Element\ForeignKey;
use Phoenix\Database\Element\Table;

class Dumper
{
    private $indent;

    private $baseIndent;

    private $defaultSettings = [
        ColumnSettings::SETTING_AUTOINCREMENT => false,
        ColumnSettings::SETTING_NULL => false,
        ColumnSettings::SETTING_DEFAULT => null,
        ColumnSettings::SETTING_SIGNED => true,
        ColumnSettings::SETTING_LENGTH => [null, ''],
        ColumnSettings::SETTING_DECIMALS => [null, ''],
        ColumnSettings::SETTING_VALUES => null,
        ColumnSettings::SETTING_CHARSET => [null, ''],
        ColumnSettings::SETTING_COLLATION => [null, ''],
    ];

    public function __construct($indent, $baseIndent = 0)
    {
        $this->indent = $indent;
        $this->baseIndent = $baseIndent;
    }

    /**
     * @param Table[] $tables
     * @return string
     */
    public function dumpTablesUp(array $tables = [])
    {
        $tableMigrations = [];
        foreach ($tables as $table) {
            $tableMigration = $this->indent() . "\$this->table('{$table->getName()}'";
            if ($table->getPrimary()) {
                $tableMigration .= ", " . $this->columnsToString($table->getPrimary());
            }
            $tableMigration .= ")\n";
            if ($table->getCharset()) {
                $tableMigration .= $this->indent(1) . "->setCharset('{$table->getCharset()}')\n";
            }
            if ($table->getCollation()) {
                $tableMigration .= $this->indent(1) . "->setCollation('{$table->getCollation()}')\n";
            }
            foreach ($table->getColumns() as $column) {
                $tableMigration .= $this->indent(1) . "->addColumn('{$column->getName()}', '{$column->getType()}'" . $this->settingsToString($column, $table) . ")\n";
            }
            foreach ($table->getIndexes() as $index) {
                $tableMigration .= $this->indent(1) . "->addIndex(";
                $indexColumns = $index->getColumns();
                $tableMigration .= $this->columnsToString($indexColumns) . ", '" . strtolower($index->getType()) . "', '" . strtolower($index->getMethod()) . "', '{$index->getName()}')\n";
            }
            $tableMigration .= $this->indent(1) . "->create();";
            $tableMigrations[] = $tableMigration;
        }
        return implode("\n\n", $tableMigrations);
    }

    /**
     * @param Table[] $tables
     * @return string
     */
    public function dumpForeignKeysUp(array $tables = [])
    {
        $foreignKeysMigrations = [];
        foreach ($tables as $table) {
            $foreignKeys = $table->getForeignKeys();
            if (count($foreignKeys) === 0) {
                continue;
            }
            $foreignKeysMigration = $this->indent() . "\$this->table('{$table->getName()}')\n";
            foreach ($foreignKeys as $foreignKey) {
                $foreignKeysMigration .= $this->indent(1) . "->addForeignKey(";
                $foreignKeysMigration .= $this->columnsToString($foreignKey->getColumns()) . ", '{$foreignKey->getReferencedTable()}'";
                $referencedColumns = $foreignKey->getReferencedColumns();
                $onDelete = strtolower($foreignKey->getOnDelete());
                $onUpdate = strtolower($foreignKey->getOnUpdate());

                if ($onDelete !== ForeignKey::DEFAULT_ACTION || $onUpdate !== ForeignKey::DEFAULT_ACTION || $referencedColumns !== ['id']) {
                    $foreignKeysMigration .= ', ' . $this->columnsToString($referencedColumns);
                }
                if ($onDelete !== ForeignKey::DEFAULT_ACTION || $onUpdate !== ForeignKey::DEFAULT_ACTION) {
                    $foreignKeysMigration .= ", '$onDelete'";
                }
                if ($onUpdate !== ForeignKey::DEFAULT_ACTION) {
                    $foreignKeysMigration .= ", '$onUpdate'";
                }
                $foreignKeysMigration .= ")\n";
            }
            $foreignKeysMigration .= $this->indent(1) . "->save();";
            $foreignKeysMigrations[] = $foreignKeysMigration;
        }
        return implode("\n\n", $foreignKeysMigrations);
    }

    /**
     * @param array $data data for migration in format table => rows
     * @return string
     */
    public function dumpDataUp(array $data = [])
    {
        $dataMigrations = [];
        foreach ($data as $table => $rows) {
            if (empty($rows)) {
                continue;
            }
            $dataMigration = "{$this->indent()}\$this->insert('$table', [\n";
            foreach ($rows as $row) {
                $dataMigration .= $this->indent(1) . "[\n";
                foreach ($row as $column => $value) {
                    $dataMigration .= "{$this->indent(2)}'$column' => '" . addslashes($value) . "',\n";
                }
                $dataMigration .= "{$this->indent(1)}],\n";
            }
            $dataMigration .= "{$this->indent()}]);";
            $dataMigrations[] = $dataMigration;
        }
        return implode("\n\n", $dataMigrations);
    }

    /**
     * @param Table[] $tables
     * @return string
     */
    public function dumpTablesDown(array $tables = [])
    {
        $downMigrations = [];
        foreach ($tables as $table) {
            $downMigration = $this->indent() . "\$this->table('{$table->getName()}')\n";
            $downMigration .= $this->indent(1) . "->drop();";
            $downMigrations[] = $downMigration;
        }
        return implode("\n\n", $downMigrations);
    }

    /**
     * @param Table[] $tables
     * @return string
     */
    public function dumpForeignKeysDown(array $tables = [])
    {
        $downForeignKeysMigrations = [];
        foreach ($tables as $table) {
            $foreignKeys = $table->getForeignKeys();
            if (count($foreignKeys) === 0) {
                continue;
            }
            $foreignKeysMigration = $this->indent() . "\$this->table('{$table->getName()}')\n";
            foreach ($foreignKeys as $foreignKey) {
                $foreignKeysMigration .= $this->indent(1) . "->dropForeignKey({$this->columnsToString($foreignKey->getColumns())})\n";
            }
            $foreignKeysMigration .= $this->indent(1) . "->save();";
            $downForeignKeysMigrations[] = $foreignKeysMigration;
        }
        return implode("\n\n", $downForeignKeysMigrations);
    }

    private function indent($multiplier = 0)
    {
        return str_repeat($this->indent, $multiplier + $this->baseIndent);
    }

    private function columnsToString(array $columns)
    {
        $columns = array_map(function ($column) {
            return "'$column'";
        }, $columns);
        $implodedColumns = implode(', ', $columns);
        return count($columns) > 1 ? '[' . $implodedColumns . ']' : $implodedColumns;
    }

    private function valuesToString(array $values)
    {
        $values = array_map(function ($value) {
            return "'$value'";
        }, $values);
        return '[' . implode(', ', $values) . ']';
    }

    private function settingsToString(Column $column, Table $table)
    {
        $settings = $column->getSettings();
        $defaultSettings = $this->defaultSettings($column, $table);

        $settingsList = [];
        foreach ($settings->getSettings() as $setting => $value) {
            if (is_array($defaultSettings[$setting]) && in_array($value, $defaultSettings[$setting])) {
                continue;
            } elseif ($value === $defaultSettings[$setting]) {
                continue;
            }
            $value = $this->transformValue($value);
            $settingsList[] = "'$setting' => $value";
        }
        if (empty($settingsList)) {
            return '';
        }
        return ', [' . implode(', ', $settingsList) . ']';
    }

    private function defaultSettings(Column $column, Table $table)
    {
        $defaultSettings = $this->defaultSettings;
        $defaultSettings[ColumnSettings::SETTING_CHARSET][] = $table->getCharset();
        $defaultSettings[ColumnSettings::SETTING_COLLATION][] = $table->getCollation();

        $type = $column->getType();
        if ($type === Column::TYPE_INTEGER) {
            $defaultSettings[ColumnSettings::SETTING_LENGTH][] = 11;
        } elseif ($type === Column::TYPE_BIG_INTEGER) {
            $defaultSettings[ColumnSettings::SETTING_LENGTH][] = 20;
        } elseif (in_array($type, [Column::TYPE_STRING, Column::TYPE_CHAR, Column::TYPE_BINARY, Column::TYPE_VARBINARY], true)) {
            $defaultSettings[ColumnSettings::SETTING_LENGTH][] = 255;
        } elseif (in_array($type, [Column::TYPE_NUMERIC, Column::TYPE_DECIMAL, Column::TYPE_FLOAT, Column::TYPE_DOUBLE], true)) {
            $defaultSettings[ColumnSettings::SETTING_LENGTH][] = 10;
            $defaultSettings[ColumnSettings::SETTING_DECIMALS][] = 0;
        }
        return $defaultSettings;
    }

    private function transformValue($value)
    {
        if (is_bool($value)) {
            $value = $value ? 'true' : 'false';
        } elseif (is_array($value)) {
            $value = $this->valuesToString($value);
        } elseif (!is_numeric($value)) {
            $value = "'$value'";
        }
        return $value;
    }
}
