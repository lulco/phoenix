<?php

namespace Phoenix\Database\Adapter;

use PDO;
use Phoenix\Database\Element\Column;
use Phoenix\Database\Element\ColumnSettings;
use Phoenix\Database\Element\Index;
use Phoenix\Database\Element\MigrationTable;
use Phoenix\Database\QueryBuilder\MysqlQueryBuilder;

class MysqlAdapter extends PdoAdapter
{
    /**
     * @return MysqlQueryBuilder
     */
    public function getQueryBuilder()
    {
        if (!$this->queryBuilder) {
            $this->queryBuilder = new MysqlQueryBuilder();
        }
        return $this->queryBuilder;
    }

    protected function loadDatabase()
    {
        return $this->execute('SELECT database()')->fetchColumn();
    }

    protected function loadTables($database)
    {
        return $this->execute(sprintf("SELECT TABLE_NAME AS table_name, TABLE_COLLATION AS table_collation, TABLE_COMMENT as table_comment FROM information_schema.TABLES WHERE TABLE_SCHEMA = '%s' ORDER BY TABLE_NAME", $database))->fetchAll(PDO::FETCH_ASSOC);
    }

    protected function createMigrationTable(array $table)
    {
        $migrationTable = parent::createMigrationTable($table);
        if ($table['table_collation']) {
            list($charset,) = explode('_', $table['table_collation'], 2);
            $migrationTable->setCharset($charset);
            $migrationTable->setCollation($table['table_collation']);
        }
        if ($table['table_comment']) {
            $migrationTable->setComment($table['table_comment']);
        }
        return $migrationTable;
    }

    private function remapType($type)
    {
        $types = [
            'int' => Column::TYPE_INTEGER,
            'tinyint' => Column::TYPE_TINY_INTEGER,
            'smallint' => Column::TYPE_SMALL_INTEGER,
            'mediumint' => Column::TYPE_MEDIUM_INTEGER,
            'bigint' => Column::TYPE_BIG_INTEGER,
            'varchar' => Column::TYPE_STRING,
            'linestring' => Column::TYPE_LINE,
        ];
        return isset($types[$type]) ? $types[$type] : $type;
    }

    protected function loadColumns($database)
    {
        $columns = $this->execute(sprintf("SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = '%s' ORDER BY TABLE_NAME, ORDINAL_POSITION", $database))->fetchAll(PDO::FETCH_ASSOC);
        $tablesColumns = [];
        foreach ($columns as $column) {
            $tablesColumns[$column['TABLE_NAME']][] = $column;
        }
        return $tablesColumns;
    }

    protected function addColumn(MigrationTable $migrationTable, array $column)
    {
        $type = $this->remapType($column['DATA_TYPE']);
        $settings = $this->prepareSettings($column);
        if ($type == Column::TYPE_CHAR && $settings[ColumnSettings::SETTING_LENGTH] == 36) {
            $type = Column::TYPE_UUID;
            $settings[ColumnSettings::SETTING_LENGTH] = null;
        } elseif ($type == Column::TYPE_TINY_INTEGER && $settings[ColumnSettings::SETTING_LENGTH] == 1) {
            $type = Column::TYPE_BOOLEAN;
            $settings[ColumnSettings::SETTING_LENGTH] = null;
            $settings[ColumnSettings::SETTING_DEFAULT] = (bool)$settings[ColumnSettings::SETTING_DEFAULT];
        }
        $migrationTable->addColumn($column['COLUMN_NAME'], $type, $settings);
    }

    private function prepareSettings($column)
    {
        preg_match('/(.*?)\((.*?)\)(.*)/', $column['COLUMN_TYPE'], $matches);
        $values = null;
        if ($column['DATA_TYPE'] == Column::TYPE_ENUM || $column['DATA_TYPE'] == Column::TYPE_SET) {
            $values = explode('\',\'', substr($matches[2], 1, -1));
        }
        list($length, $decimals) = $this->getLengthAndDecimals(isset($matches[2]) ? $matches[2] : null);
        return [
            ColumnSettings::SETTING_AUTOINCREMENT => $column['EXTRA'] == 'auto_increment',
            ColumnSettings::SETTING_NULL => $column['IS_NULLABLE'] == 'YES',
            ColumnSettings::SETTING_DEFAULT => $column['COLUMN_DEFAULT'],
            ColumnSettings::SETTING_LENGTH => $length,
            ColumnSettings::SETTING_DECIMALS => $decimals,
            ColumnSettings::SETTING_SIGNED => !(isset($matches[3]) && trim($matches[3]) == 'unsigned'),
            ColumnSettings::SETTING_CHARSET => $column['CHARACTER_SET_NAME'],
            ColumnSettings::SETTING_COLLATION => $column['COLLATION_NAME'],
            ColumnSettings::SETTING_VALUES => $values,
        ];
    }

    private function getLengthAndDecimals($lengthAndDecimals = null)
    {
        if ($lengthAndDecimals === null) {
            return [null, null];
        }

        $length = (int) $lengthAndDecimals;
        $decimals = null;
        if (strpos($lengthAndDecimals, ',')) {
            list($length, $decimals) = array_map('intval', explode(',', $lengthAndDecimals, 2));
        }
        return [$length, $decimals];
    }

    protected function loadIndexes($database)
    {
        $indexes = $this->execute(sprintf("SELECT * FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = '%s'", $database))->fetchAll(PDO::FETCH_ASSOC);
        $tablesIndexes = [];
        foreach ($indexes as $index) {
            if (!isset($tablesIndexes[$index['TABLE_NAME']])) {
                $tablesIndexes[$index['TABLE_NAME']] = [];
            }
            $tablesIndexes[$index['TABLE_NAME']][$index['INDEX_NAME']]['columns'][$index['SEQ_IN_INDEX']] = $index['COLUMN_NAME'];
            $tablesIndexes[$index['TABLE_NAME']][$index['INDEX_NAME']]['type'] = $index['NON_UNIQUE'] == 0 ? Index::TYPE_UNIQUE : ($index['INDEX_TYPE'] == 'FULLTEXT' ? Index::TYPE_FULLTEXT : Index::TYPE_NORMAL);
            $tablesIndexes[$index['TABLE_NAME']][$index['INDEX_NAME']]['method'] = $index['INDEX_TYPE'] == 'FULLTEXT' ? Index::METHOD_DEFAULT : $index['INDEX_TYPE'];
        }
        return $tablesIndexes;
    }

    protected function loadForeignKeys($database)
    {
        $query = sprintf('SELECT * FROM information_schema.KEY_COLUMN_USAGE
INNER JOIN information_schema.REFERENTIAL_CONSTRAINTS ON information_schema.KEY_COLUMN_USAGE.CONSTRAINT_NAME = information_schema.REFERENTIAL_CONSTRAINTS.CONSTRAINT_NAME
AND information_schema.KEY_COLUMN_USAGE.CONSTRAINT_SCHEMA = information_schema.REFERENTIAL_CONSTRAINTS.CONSTRAINT_SCHEMA
WHERE information_schema.KEY_COLUMN_USAGE.TABLE_SCHEMA = "%s";', $database);
        $foreignKeyColumns = $this->execute($query)->fetchAll(PDO::FETCH_ASSOC);
        $foreignKeys = [];
        foreach ($foreignKeyColumns as $foreignKeyColumn) {
            $foreignKeys[$foreignKeyColumn['TABLE_NAME']][$foreignKeyColumn['CONSTRAINT_NAME']]['columns'][] = $foreignKeyColumn['COLUMN_NAME'];
            $foreignKeys[$foreignKeyColumn['TABLE_NAME']][$foreignKeyColumn['CONSTRAINT_NAME']]['referenced_table'] = $foreignKeyColumn['REFERENCED_TABLE_NAME'];
            $foreignKeys[$foreignKeyColumn['TABLE_NAME']][$foreignKeyColumn['CONSTRAINT_NAME']]['referenced_columns'][] = $foreignKeyColumn['REFERENCED_COLUMN_NAME'];
            $foreignKeys[$foreignKeyColumn['TABLE_NAME']][$foreignKeyColumn['CONSTRAINT_NAME']]['on_update'] = $foreignKeyColumn['UPDATE_RULE'];
            $foreignKeys[$foreignKeyColumn['TABLE_NAME']][$foreignKeyColumn['CONSTRAINT_NAME']]['on_delete'] = $foreignKeyColumn['DELETE_RULE'];
        }
        return $foreignKeys;
    }

    protected function escapeString($string)
    {
        return '`' . $string . '`';
    }

    protected function createRealValue($value)
    {
        return is_array($value) ? implode(',', $value) : $value;
    }
}
