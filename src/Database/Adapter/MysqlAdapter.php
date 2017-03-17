<?php

namespace Phoenix\Database\Adapter;

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

    protected function loadStructure()
    {
        $database = $this->execute('SELECT database()')->fetchColumn();
        $structure = new Structure();
        $tables = $this->execute("SELECT * FROM information_schema.TABLES WHERE TABLE_SCHEMA = '$database' ORDER BY TABLE_NAME")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($tables as $table) {
            $migrationTable = $this->tableInfo($table['TABLE_NAME'], $database);
            if ($migrationTable) {
                $structure->update($migrationTable);
            }
        }
        return $structure;
    }

    private function tableInfo($table, $database)
    {
        $columns = $this->execute(sprintf('SHOW FULL COLUMNS FROM `%s`', $table))->fetchAll(PDO::FETCH_ASSOC);
        $migrationTable = new MigrationTable($table);
        foreach ($columns as $column) {
            $type = $column['Type'];
            preg_match('/(.*?)\((.*?)\)/', $column['Type'], $matches);

            if (isset($matches[1]) && $matches[1] != '') {
                $type = $matches[1];
            }

            if ($type == 'int') {
                $type = Column::TYPE_INTEGER;
            } elseif ($type == 'varchar') {
                $type = Column::TYPE_STRING;
            }

            $length = null;
            $decimals = null;
            if (isset($matches[2])) {
                if (strpos($matches[2], ',')) {
                    list($length, $decimals) = explode(',', $matches[2], 2);
                    $length = (int) $length;
                    $decimals = (int) $decimals;
                } else {
                    $length = (int) $matches[2];
                }
            }

            $settings = [
                'autoincrement' => $column['Extra'] == 'auto_increment',
                'null' => $column['Null'] == 'YES',
                'default' => $column['Default'],
                'length' => $length,
                'decimals' => $decimals,
            ];
            if ($column['Collation']) {
                $settings['collation'] = $column['Collation'];
            }
            $migrationTable->addColumn($column['Field'], $type, $settings);
        }
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
            $indexesTypesAndMethods[$indexRow['Key_name']]['type'] = $indexRow['Non_unique'] == 0 ? Index::TYPE_UNIQUE : Index::TYPE_NORMAL;
            $indexesTypesAndMethods[$indexRow['Key_name']]['method'] = $indexRow['Index_type'];
        }
        $migrationTable->addPrimary($primaryKeys);
        foreach ($indexes as $name => $columns) {
            ksort($columns);
            $migrationTable->addIndex($columns, $indexesTypesAndMethods[$name]['type'], $indexesTypesAndMethods[$name]['method'], $name);
        }

        // TODO foreign keys
        
        /**
         * SELECT * FROM information_schema.KEY_COLUMN_USAGE
INNER JOIN information_schema.REFERENTIAL_CONSTRAINTS ON information_schema.KEY_COLUMN_USAGE.CONSTRAINT_NAME = information_schema.REFERENTIAL_CONSTRAINTS.CONSTRAINT_NAME
WHERE information_schema.KEY_COLUMN_USAGE.TABLE_SCHEMA = "phoenix" AND information_schema.KEY_COLUMN_USAGE.TABLE_NAME = "table_2";
         */


//        $foreignKeys = $this->execute(sprintf('SELECT * FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = "%s" AND TABLE_NAME = "%s"', $database, $table));
//        foreach ($foreignKeys as $foreignKey) {
//            if ($foreignKey['CONSTRAINT_NAME'] == 'PRIMARY') {
//                continue;
//            }
//            $migrationTable->addfore
//        }


        return $migrationTable;
    }

    protected function createRealValue($value)
    {
        return is_array($value) ? implode(',', $value) : $value;
    }

    protected function escapeString($string)
    {
        return '`' . $string . '`';
    }
}
