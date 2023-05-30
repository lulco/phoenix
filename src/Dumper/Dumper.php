<?php

declare(strict_types=1);

namespace Phoenix\Dumper;

use Phoenix\Database\Element\Column;
use Phoenix\Database\Element\ColumnSettings;
use Phoenix\Database\Element\ForeignKey;
use Phoenix\Database\Element\IndexColumn;
use Phoenix\Database\Element\MigrationTable;

final class Dumper
{
    private string $indent;

    private int $baseIndent;

    private bool $tableExistCondition;

    private bool $autoIncrement;

    /** @var array<string, mixed> */
    private array $defaultSettings = [
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

    public function __construct(string $indent, int $baseIndent = 0, bool $tableExistCondition = false, bool $autoIncrement = false)
    {
        $this->indent = $indent;
        $this->baseIndent = $baseIndent;
        $this->tableExistCondition = $tableExistCondition;
        $this->autoIncrement = $autoIncrement;
    }

    /**
     * @param MigrationTable[] $tables
     */
    public function dumpTables(array $tables, string $dumpType): string
    {
        $tableMigrations = [];
        foreach ($tables as $table) {
            $indentMultiplier = $defaultIndentMultiplier = 0;
            $tableMigration = '';
            if ($dumpType === 'up' && $this->tableExistCondition === true) {
                $tableMigration .= $this->indent($defaultIndentMultiplier) . "if (!\$this->tableExists('{$table->getName()}')) {\n";
                $indentMultiplier = 1;
            }
            $tableMigration .= $this->indent($indentMultiplier) . "\$this->table('{$table->getName()}'";
            $primaryColumns = $table->getPrimaryColumnNames();
            if ($primaryColumns) {
                $tableMigration .= ", " . $this->columnsToString($primaryColumns);
            } elseif ($table->getAction() === MigrationTable::ACTION_CREATE) {
                $tableMigration .= ", false";
            }
            $tableMigration .= ")\n";
            if ($table->getAutoIncrement() && $table->getAutoIncrement() !== 1 && $this->autoIncrement) {
                $tableMigration .= $this->indent($indentMultiplier + 1) . "->setAutoincrement({$table->getAutoIncrement()})\n";
            }
            if ($table->getCharset()) {
                $tableMigration .= $this->indent($indentMultiplier + 1) . "->setCharset('{$table->getCharset()}')\n";
            }
            if ($table->getCollation()) {
                $tableMigration .= $this->indent($indentMultiplier + 1) . "->setCollation('{$table->getCollation()}')\n";
            }
            $comment = $table->getComment();
            if ($comment) {
                $tableMigration .= $this->indent($indentMultiplier + 1) . "->setComment('{$this->sanitizeSingleQuote($comment)}')\n";
            }
            if ($table->hasPrimaryKeyToDrop()) {
                $tableMigration .= $this->indent($indentMultiplier + 1) . "->dropPrimaryKey()\n";
            }
            foreach ($table->getColumnsToDrop() as $column) {
                $tableMigration .= $this->indent($indentMultiplier + 1) . "->dropColumn('$column')\n";
            }
            foreach ($table->getColumnsToChange() as $oldColumnName => $column) {
                $tableMigration .= $this->indent($indentMultiplier + 1) . "->changeColumn('$oldColumnName', '{$column->getName()}', '{$column->getType()}'" . $this->settingsToString($column, $table) . ")\n";
            }
            if ($table->getPrimaryColumns()) {
                $primaryColumnList = [];
                foreach ($table->getPrimaryColumns() as $primaryColumn) {
                    $primaryColumnList[] = "new \Phoenix\Database\Element\Column('{$primaryColumn->getName()}', '{$primaryColumn->getType()}'" . $this->settingsToString($primaryColumn, $table) . ")";
                }
                $addedPrimaryColumns = implode(', ', $primaryColumnList);
                $tableMigration .= $this->indent($indentMultiplier + 1) . "->addPrimaryColumns([$addedPrimaryColumns])\n";
            }
            foreach ($table->getColumns() as $column) {
                $tableMigration .= $this->indent($indentMultiplier + 1) . "->addColumn('{$column->getName()}', '{$column->getType()}'" . $this->settingsToString($column, $table) . ")\n";
            }
            foreach ($table->getUniqueConstraintsToDrop() as $uniqueConstraintName) {
                $tableMigration .= $this->indent($indentMultiplier + 1) . "->dropUniqueConstraint('$uniqueConstraintName')\n";
            }
            foreach ($table->getUniqueConstraints() as $uniqueConstraint) {
                $uniqueConstraintName = $uniqueConstraint->getName();
                $columns = $uniqueConstraint->getColumns();
                $uniqueConstraintColumns = count($columns) > 1 ? '[' . "'" . implode("', '", $columns) . "'" . ']' : "'" . implode('', $columns) . "'";
                $tableMigration .= $this->indent($indentMultiplier + 1) . "->addUniqueConstraint($uniqueConstraintColumns, '$uniqueConstraintName')\n";
            }
            foreach ($table->getIndexesToDrop() as $indexName) {
                if (in_array($indexName, $table->getUniqueConstraintsToDrop(), true)) {
                    continue;
                }
                $tableMigration .= $this->indent($indentMultiplier + 1) . "->dropIndexByName('$indexName')\n";
            }
            $uniqueConstraintNames = array_map(fn($value): string => $value->getName(), $table->getUniqueConstraints());
            foreach ($table->getIndexes() as $index) {
                if (in_array($index->getName(), $uniqueConstraintNames, true)) {
                    continue;
                }
                $tableMigration .= $this->indent($indentMultiplier + 1) . "->addIndex(";
                $tableMigration .= $this->indexColumnsToString($index->getColumns()) . ", '" . strtolower($index->getType()) . "', '" . strtolower($index->getMethod()) . "', '{$index->getName()}')\n";
            }
            $action = $table->getAction() === MigrationTable::ACTION_ALTER ? 'save' : $table->getAction();
            $tableMigration .= $this->indent($indentMultiplier + 1) . "->$action();";
            if ($dumpType === 'up' && $this->tableExistCondition === true) {
                $tableMigration .= "\n" . $this->indent($defaultIndentMultiplier) . "}";
            }
            $tableMigrations[] = $tableMigration;
        }
        return implode("\n\n", $tableMigrations);
    }

    /**
     * @param MigrationTable[] $tables
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

            $indentMultiplier = $defaultIndentMultiplier = 0;
            $foreignKeysMigration = '';
            if ($this->tableExistCondition === true) {
                $foreignKeysMigration .= $this->indent($defaultIndentMultiplier) . "if (!\$this->tableExists('{$table->getName()}')) {\n";
                $indentMultiplier = 1;
            }
            $foreignKeysMigration .= $this->indent($indentMultiplier) . "\$this->table('{$table->getName()}')\n";
            foreach ($foreignKeysToDrop as $foreignKeyToDrop) {
                $foreignKeysMigration .= $this->indent($indentMultiplier + 1) . "->dropForeignKey('{$foreignKeyToDrop}')\n";
            }
            foreach ($foreignKeys as $foreignKey) {
                $foreignKeysMigration .= $this->indent($indentMultiplier + 1) . "->addForeignKey(";
                $foreignKeysMigration .= $this->columnsToString($foreignKey->getColumns()) . ", '{$foreignKey->getReferencedTable()}'";
                $foreignKeysMigration .= $this->foreignKeyActions($foreignKey);
                $foreignKeysMigration .= ")\n";
            }
            $foreignKeysMigration .= $this->indent($indentMultiplier + 1) . "->save();";
            if ($this->tableExistCondition === true) {
                $foreignKeysMigration .= "\n" . $this->indent($defaultIndentMultiplier) . "}";
            }
            $foreignKeysMigrations[] = $foreignKeysMigration;
        }
        return implode("\n\n", $foreignKeysMigrations);
    }

    /**
     * @param array<string, array<string, mixed>> $data data for migration in format table => rows
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
                    if ($value === null) {
                        $dataMigration .= "{$this->indent(2)}'$column' => null,\n";
                    } elseif ($value === false) {
                        $dataMigration .= "{$this->indent(2)}'$column' => false,\n";
                    } elseif ($value === true) {
                        $dataMigration .= "{$this->indent(2)}'$column' => true,\n";
                    } else {
                        $dataMigration .= "{$this->indent(2)}'$column' => '" . $this->sanitizeSingleQuote((string)$value) . "',\n";
                    }
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
     */
    private function valuesToString(array $values): string
    {
        $values = array_map(function ($value) {
            return "'" . $this->sanitizeSingleQuote((string)$value) . "'";
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
     */
    private function transformValue($value): string
    {
        if (is_bool($value)) {
            $value = $value ? 'true' : 'false';
        } elseif (is_array($value)) {
            $value = $this->valuesToString($value);
        } elseif (!is_numeric($value)) {
            $value = "'" . $this->sanitizeSingleQuote($value) . "'";
        }
        return (string)$value;
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

    private function sanitizeSingleQuote(string $input): string
    {
        return str_replace("'", "\'", $input);
    }
}
