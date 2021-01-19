<?php

namespace Dumper;

use Phoenix\Database\Element\Column;
use Phoenix\Database\Element\ColumnSettings;
use Phoenix\Database\Element\ForeignKey;
use Phoenix\Database\Element\MigrationTable;
use Phoenix\Database\Element\IndexColumn;

class Dumper
{
    /** @var string */
    private $indent;

    /** @var int  */
    private $baseIndent;

    /** @var array<string, mixed> */
    private $defaultSettings = [
        ColumnSettings::SETTING_AUTOINCREMENT => false,
        ColumnSettings::SETTING_NULL => false,
        ColumnSettings::SETTING_DEFAULT => null,
        ColumnSettings::SETTING_SIGNED => true,
        ColumnSettings::SETTING_LENGTH => [null, ''],
        ColumnSettings::SETTING_DECIMALS => [null, ''],
        ColumnSettings::SETTING_CHARSET => [null, ''],
        ColumnSettings::SETTING_COLLATION => [null, ''],
        ColumnSettings::SETTING_VALUES => null,
        ColumnSettings::SETTING_COMMENT => [null, ''],
    ];

    public function __construct(string $indent, int $baseIndent = 0)
    {
        $this->indent = $indent;
        $this->baseIndent = $baseIndent;
    }

    /**
     * @param MigrationTable[] $tables
     * @return string
     */
    public function dumpTables(array $tables): string
    {
        $tableMigrations = [];
        foreach ($tables as $table) {
            $tableMigration = $this->indent() . "\$this->table('{$table->getName()}'";
            $primaryColumns = $table->getPrimaryColumnNames();
            if ($primaryColumns) {
                $tableMigration .= ", " . $this->columnsToString($primaryColumns);
            }
            $tableMigration .= ")\n";
            if ($table->getCharset()) {
                $tableMigration .= $this->indent(1) . "->setCharset('{$table->getCharset()}')\n";
            }
            if ($table->getCollation()) {
                $tableMigration .= $this->indent(1) . "->setCollation('{$table->getCollation()}')\n";
            }
            if ($table->hasPrimaryKeyToDrop()) {
                $tableMigration .= $this->indent(1) . "->dropPrimaryKey()\n";
            }
            foreach ($table->getColumnsToDrop() as $column) {
                $tableMigration .= $this->indent(1) . "->dropColumn('$column')\n";
            }
            foreach ($table->getColumnsToChange() as $oldColumnName => $column) {
                $tableMigration .= $this->indent(1) . "->changeColumn('$oldColumnName', '{$column->getName()}', '{$column->getType()}'" . $this->settingsToString($column, $table) . ")\n";
            }
            if ($table->getPrimaryColumns()) {
                $primaryColumnList = [];
                foreach ($table->getPrimaryColumns() as $primaryColumn) {
                    $primaryColumnList[] = "new \Phoenix\Database\Element\Column('{$primaryColumn->getName()}', '{$primaryColumn->getType()}'" . $this->settingsToString($primaryColumn, $table) . ")";
                }
                $addedPrimaryColumns = implode(', ', $primaryColumnList);
                $tableMigration .= $this->indent(1) . "->addPrimaryColumns([$addedPrimaryColumns])\n";
            }
            foreach ($table->getColumns() as $column) {
                $tableMigration .= $this->indent(1) . "->addColumn('{$column->getName()}', '{$column->getType()}'" . $this->settingsToString($column, $table) . ")\n";
            }
            foreach ($table->getIndexesToDrop() as $indexName) {
                $tableMigration .= $this->indent(1) . "->dropIndexByName('$indexName')\n";
            }
            foreach ($table->getIndexes() as $index) {
                $tableMigration .= $this->indent(1) . "->addIndex(";
                $tableMigration .= $this->indexColumnsToString($index->getColumns()) . ", '" . strtolower($index->getType()) . "', '" . strtolower($index->getMethod()) . "', '{$index->getName()}')\n";
            }
            $action = $table->getAction() === MigrationTable::ACTION_ALTER ? 'save' : $table->getAction();
            $tableMigration .= $this->indent(1) . "->$action();";
            $tableMigrations[] = $tableMigration;
        }
        return implode("\n\n", $tableMigrations);
    }

    /**
     * @param MigrationTable[] $tables
     * @return string
     */
    public function dumpForeignKeys(array $tables): string
    {
        $foreignKeysMigrations = [];
        foreach ($tables as $table) {
            $foreignKeysToDrop = $table->getForeignKeysToDrop();
            $foreignKeys = $table->getForeignKeys();
            if (count($foreignKeysToDrop) === 0 && count($foreignKeys) === 0) {
                continue;
            }

            $foreignKeysMigration = $this->indent() . "\$this->table('{$table->getName()}')\n";
            foreach ($foreignKeysToDrop as $foreignKeyToDrop) {
                $foreignKeysMigration .= $this->indent(1) . "->dropForeignKey('{$foreignKeyToDrop}')\n";
            }
            foreach ($foreignKeys as $foreignKey) {
                $foreignKeysMigration .= $this->indent(1) . "->addForeignKey(";
                $foreignKeysMigration .= $this->columnsToString($foreignKey->getColumns()) . ", '{$foreignKey->getReferencedTable()}'";
                $foreignKeysMigration .= $this->foreignKeyActions($foreignKey);
                $foreignKeysMigration .= ")\n";
            }
            $foreignKeysMigration .= $this->indent(1) . "->save();";
            $foreignKeysMigrations[] = $foreignKeysMigration;
        }
        return implode("\n\n", $foreignKeysMigrations);
    }

    /**
     * @param array<string, array<string, mixed>> $data data for migration in format table => rows
     * @return string
     */
    public function dumpDataUp(array $data = []): string
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

    private function indent(int $multiplier = 0): string
    {
        return str_repeat($this->indent, $multiplier + $this->baseIndent);
    }

    /**
     * @param string[] $columns
     * @return string
     */
    private function columnsToString(array $columns): string
    {
        $columns = array_map(function ($column) {
            return "'$column'";
        }, $columns);
        $implodedColumns = implode(', ', $columns);
        return count($columns) > 1 ? '[' . $implodedColumns . ']' : $implodedColumns;
    }

    /**
     * @param string[] $values
     * @return string
     */
    private function valuesToString(array $values): string
    {
        $values = array_map(function ($value) {
            return "'$value'";
        }, $values);
        return '[' . implode(', ', $values) . ']';
    }

    private function settingsToString(Column $column, MigrationTable $table): string
    {
        $settings = $column->getSettings();
        $defaultSettings = $this->defaultSettings($column, $table);

        $settingsList = [];
        foreach ($settings->getSettings() as $setting => $value) {
            if (is_array($defaultSettings[$setting]) && in_array($value, $defaultSettings[$setting], true)) {
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

    /**
     * @param IndexColumn[] $indexColumns
     * @return string
     */
    private function indexColumnsToString(array $indexColumns): string
    {
        $columns = [];
        $useOnlyNames = true;
        foreach ($indexColumns as $indexColumn) {
            $indexColumnSettings = $indexColumn->getSettings()->getNonDefaultSettings();
            $columns[$indexColumn->getName()] = $indexColumnSettings;
            if (!empty($indexColumnSettings)) {
                $useOnlyNames = false;
            }
        }

        if ($useOnlyNames) {
            return $this->columnsToString(array_keys($columns));
        }

        $columnsList = [];
        foreach ($columns as $column => $settings) {
            $settingsList = [];
            foreach ($settings as $setting => $value) {
                $value = $this->transformValue($value);
                $settingsList[] = "'$setting' => $value";
            }
            $columnsList[] = "new \\" . IndexColumn::class . "('$column', [" . implode(', ', $settingsList) . "])";
        }
        $implodedColumns = implode(', ', $columnsList);
        return count($columnsList) > 1 ? '[' . $implodedColumns . ']' : $implodedColumns;
    }

    /**
     * @param Column $column
     * @param MigrationTable $table
     * @return array<string, mixed>
     */
    private function defaultSettings(Column $column, MigrationTable $table): array
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

    /**
     * @param mixed $value
     * @return mixed
     */
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

    private function foreignKeyActions(ForeignKey $foreignKey): string
    {
        $onDelete = strtolower($foreignKey->getOnDelete());
        $onUpdate = strtolower($foreignKey->getOnUpdate());
        $referencedColumns = $foreignKey->getReferencedColumns();

        $foreignKeyActions = '';
        if ($onDelete !== ForeignKey::DEFAULT_ACTION || $onUpdate !== ForeignKey::DEFAULT_ACTION || $referencedColumns !== ['id']) {
            $foreignKeyActions .= ', ' . $this->columnsToString($referencedColumns);
        }
        if ($onDelete !== ForeignKey::DEFAULT_ACTION || $onUpdate !== ForeignKey::DEFAULT_ACTION) {
            $foreignKeyActions .= ", '$onDelete'";
        }
        if ($onUpdate !== ForeignKey::DEFAULT_ACTION) {
            $foreignKeyActions .= ", '$onUpdate'";
        }
        return $foreignKeyActions;
    }
}
