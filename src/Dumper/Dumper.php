<?php

namespace Dumper;

use Phoenix\Database\Element\Column;
use Phoenix\Database\Element\ColumnSettings;
use Phoenix\Database\Element\MigrationTable;

class Dumper
{
    private $indent;

    private $baseIndent;

    public function __construct($indent, $baseIndent = 0)
    {
        $this->indent = $indent;
        $this->baseIndent = $baseIndent;
    }

    /**
     * @param MigrationTable[] $tables
     * @return string
     */
    public function dumpTables(array $tables = [])
    {
        $migration = '';
        foreach ($tables as $table) {
            $migration .= $this->indent() . "\$this->table('{$table->getName()}'";
            if ($table->getPrimary()) {
                $migration .= ", " . $this->columnsToString($table->getPrimary());
            }
            $migration .= ")\n";

            foreach ($table->getColumns() as $column) {
                $migration .= $this->indent(1) . "->addColumn('{$column->getName()}', '{$column->getType()}'" . $this->settingsToString($column->getType(), $column->getSettings()) . ")\n";
            }
            foreach ($table->getIndexes() as $index) {
                $migration .= $this->indent(1) . "->addIndex(";
                $indexColumns = $index->getColumns();
                $migration .= $this->columnsToString($indexColumns) . ", '" . strtolower($index->getType()) . "', '" . strtolower($index->getMethod()) . "', '{$index->getName()}')\n";
            }
            $migration .= $this->indent(1) . "->create();\n\n";
        }
        $migration .= $this->dumpForeignKeys($tables);
        return $migration;
    }

    private function dumpForeignKeys(array $tables = [])
    {
        $foreignKeysMigration = '';
        foreach ($tables as $table) {
            $foreignKeys = $table->getForeignKeys();
            if (count($foreignKeys) == 0) {
                continue;
            }
            $foreignKeysMigration .= $this->indent() . "\$this->table('{$table->getName()}')\n";
            foreach ($foreignKeys as $foreignKey) {
                $foreignKeysMigration .= $this->indent(1) . "->addForeignKey(";
                $foreignKeysMigration .= $this->columnsToString($foreignKey->getColumns()) . ", '{$foreignKey->getReferencedTable()}', ";
                $foreignKeysMigration .= $this->columnsToString($foreignKey->getReferencedColumns()) . ", '{$foreignKey->getOnDelete()}', '{$foreignKey->getOnUpdate()}')\n";
            }
            $foreignKeysMigration .= $this->indent(1) . "->save();\n\n";
        }
        return $foreignKeysMigration;
    }

    /**
     * @param array $data data for migration in format table => rows
     * @return string
     */
    public function dumpData(array $data = [])
    {
        $dataMigration = '';
        foreach ($data as $table => $rows) {
            $dataMigration .= "{$this->indent()}\$this->insert('$table', [\n";
            foreach ($rows as $row) {
                $dataMigration .= $this->indent(1) . "[\n";
                foreach ($row as $column => $value) {
                    $dataMigration .= "{$this->indent(2)}'$column' => '" . addslashes($value) . "',\n";
                }
                $dataMigration .= "{$this->indent(1)}],\n";
            }
            $dataMigration .= "{$this->indent()}]);\n\n";
        }
        return $dataMigration;
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

    private function settingsToString($type, ColumnSettings $settings)
    {
        $defaultSettings = [
            'autoincrement' => false,
            'null' => false,
            'default' => null,
            'signed' => true,
            'length' => null,
            'decimals' => null,
            'values' => null,
        ];
        if ($type == Column::TYPE_STRING) {
            $defaultSettings['length'] = 255;
        } elseif ($type == Column::TYPE_INTEGER) {
            $defaultSettings['length'] = 11;
        } elseif ($type == Column::TYPE_BOOLEAN) {
            $defaultSettings['signed'] = false;
        } elseif ($type == Column::TYPE_TEXT) {
            $defaultSettings['null'] = true;
        }
        $settingsList = [];
        foreach ($settings->getSettings() as $setting => $value) {
            if (in_array($setting, ['charset', 'collation'])) {
                continue;
            }
            if ($value === $defaultSettings[$setting]) {
                continue;
            }
            if ($value === null) {
                $value = 'null';
            } elseif (is_array($value)) {
                $value = $this->valuesToString($value);
            } elseif (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            } elseif (!is_numeric($value)) {
                $value = "'$value'";
            }
            $settingsList[] = "'$setting' => $value";
        }
        if (empty($settingsList)) {
            return '';
        }
        return ', [' . implode(', ', $settingsList) . ']';
    }
}
