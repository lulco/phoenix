<?php

namespace Phoenix\Database\Adapter;

use LogicException;
use PDO;
use Phoenix\Database\Element\Column;
use Phoenix\Database\Element\Index;
use Phoenix\Database\Element\MigrationTable;
use Phoenix\Database\Element\Structure;
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

    public function tableInfo($table)
    {
        throw new LogicException('Not yet implemented');
    }

    protected function loadStructure()
    {
        $structure = new Structure();
        $database = $this->execute('SELECT database()')->fetchColumn();
        $tables = $this->execute("SELECT * FROM information_schema.TABLES WHERE TABLE_SCHEMA = '$database' ORDER BY TABLE_NAME")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($tables as $table) {
            $migrationTable = $this->createMigrationTable($table, $database);
            $structure->update($migrationTable);
        }
        return $structure;
    }

    private function createMigrationTable($table, $database)
    {
        $tableName = $table['TABLE_NAME'];
        $migrationTable = new MigrationTable($tableName, false);
        if ($table['TABLE_COLLATION']) {
            list($charset,) = explode('_', $table['TABLE_COLLATION'], 2);
            $migrationTable->setCharset($charset);
            $migrationTable->setCollation($table['TABLE_COLLATION']);
        }
        $this->loadColumns($migrationTable, $tableName);
        $this->loadIndexes($migrationTable, $tableName);
        $this->loadForeignKeys($migrationTable, $database, $tableName);
        $migrationTable->create();
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

    private function loadColumns(MigrationTable $migrationTable, $table)
    {
        $columns = $this->execute(sprintf('SHOW FULL COLUMNS FROM `%s`', $table))->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $column) {
            $this->addColumn($migrationTable, $column);
        }
    }

    private function addColumn(MigrationTable $migrationTable, array $column)
    {
        $values = null;
        $type = $column['Type'];
        preg_match('/(.*?)\((.*?)\)(.*)/', $column['Type'], $matches);
        if (isset($matches[1]) && $matches[1] != '') {
            $type = $matches[1];
        }
        $type = $this->remapType($type);
        if (($type == Column::TYPE_ENUM || $type == Column::TYPE_SET) && isset($matches[2])) {
            $values = explode('\',\'', substr($matches[2], 1, -1));
        }
        list($length, $decimals) = $this->getLengthAndDecimals(isset($matches[2]) ? $matches[2] : null);
        if ($type == Column::TYPE_CHAR && $length == 36) {
            $type = Column::TYPE_UUID;
            $length = null;
        }
        if ($type == Column::TYPE_TINY_INTEGER && $length == 1) {
            $type = Column::TYPE_BOOLEAN;
            $length = null;
        }
        $settings = $this->prepareSettings($column, $length, $decimals, $matches, $values);
        if ($type === Column::TYPE_BOOLEAN) {
            $settings['default'] = (bool)$settings['default'];
        }
        $migrationTable->addColumn($column['Field'], $type, $settings);
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

    private function prepareSettings($column, $length, $decimals, $matches, $values)
    {
        $collation = $column['Collation'];
        $charset = $collation ? explode('_', $collation, 2)[0] : null;
        return [
            'autoincrement' => $column['Extra'] == 'auto_increment',
            'null' => $column['Null'] == 'YES',
            'default' => $column['Default'],
            'length' => $length,
            'decimals' => $decimals,
            'signed' => !(isset($matches[3]) && trim($matches[3]) == 'unsigned'),
            'charset' => $charset,
            'collation' => $collation,
            'values' => $values,
        ];
    }

    private function loadIndexes(MigrationTable $migrationTable, $table)
    {
        $indexRows = $this->execute(sprintf('SHOW INDEX FROM `%s`', $table))->fetchAll(PDO::FETCH_ASSOC);
        $primaryKeys = [];
        $indexes = [];
        $indexesTypesAndMethods = [];
        foreach ($indexRows as $indexRow) {
            if ($indexRow['Key_name'] == 'PRIMARY') {
                $primaryKeys[$indexRow['Seq_in_index']] = $indexRow['Column_name'];
                continue;
            }
            $indexes[$indexRow['Key_name']][$indexRow['Seq_in_index']] = $indexRow['Column_name'];
            $indexesTypesAndMethods[$indexRow['Key_name']]['type'] = $indexRow['Non_unique'] == 0 ? Index::TYPE_UNIQUE : ($indexRow['Index_type'] == 'FULLTEXT' ? Index::TYPE_FULLTEXT : Index::TYPE_NORMAL);
            $indexesTypesAndMethods[$indexRow['Key_name']]['method'] = $indexRow['Index_type'] == 'FULLTEXT' ? Index::TYPE_NORMAL : $indexRow['Index_type'];
        }
        $migrationTable->addPrimary($primaryKeys);
        foreach ($indexes as $name => $columns) {
            ksort($columns);
            $migrationTable->addIndex(array_values($columns), $indexesTypesAndMethods[$name]['type'], $indexesTypesAndMethods[$name]['method'], $name);
        }
    }

    private function loadForeignKeys(MigrationTable $migrationTable, $database, $table)
    {
        $query = sprintf('SELECT * FROM information_schema.KEY_COLUMN_USAGE
INNER JOIN information_schema.REFERENTIAL_CONSTRAINTS ON information_schema.KEY_COLUMN_USAGE.CONSTRAINT_NAME = information_schema.REFERENTIAL_CONSTRAINTS.CONSTRAINT_NAME
WHERE information_schema.KEY_COLUMN_USAGE.TABLE_SCHEMA = "%s" AND information_schema.KEY_COLUMN_USAGE.TABLE_NAME = "%s";', $database, $table);
        $foreignKeyColumns = $this->execute($query)->fetchAll(PDO::FETCH_ASSOC);
        $foreignKeys = [];
        foreach ($foreignKeyColumns as $foreignKeyColumn) {
            $foreignKeys[$foreignKeyColumn['CONSTRAINT_NAME']]['columns'][] = $foreignKeyColumn['COLUMN_NAME'];
            $foreignKeys[$foreignKeyColumn['CONSTRAINT_NAME']]['referenced_table'] = $foreignKeyColumn['REFERENCED_TABLE_NAME'];
            $foreignKeys[$foreignKeyColumn['CONSTRAINT_NAME']]['referenced_columns'][] = $foreignKeyColumn['REFERENCED_COLUMN_NAME'];
            $foreignKeys[$foreignKeyColumn['CONSTRAINT_NAME']]['on_update'] = $foreignKeyColumn['UPDATE_RULE'];
            $foreignKeys[$foreignKeyColumn['CONSTRAINT_NAME']]['on_delete'] = $foreignKeyColumn['DELETE_RULE'];
        }
        foreach ($foreignKeys as $foreignKey) {
            $migrationTable->addForeignKey($foreignKey['columns'], $foreignKey['referenced_table'], $foreignKey['referenced_columns'], $foreignKey['on_delete'], $foreignKey['on_update']);
        }
    }

    protected function createRealValue($value)
    {
        return is_array($value) ? implode(',', $value) : $value;
    }
}
