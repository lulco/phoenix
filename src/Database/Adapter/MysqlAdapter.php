<?php

namespace Phoenix\Database\Adapter;

use PDO;
use Phoenix\Database\Element\Column;
use Phoenix\Database\Element\Structure;
use Phoenix\Database\QueryBuilder\MysqlQueryBuilder;
use Phoenix\Exception\DatabaseQueryExecuteException;

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
            $migrationTable = $this->tableInfo($table['TABLE_NAME']);
            if ($migrationTable) {
                $structure->update($migrationTable);
            }
        }
        return $structure;
    }

    public function tableInfo($table)
    {
        try {
            $columns = $this->execute(sprintf('SHOW FULL COLUMNS FROM `%s`', $table))->fetchAll(PDO::FETCH_ASSOC);
        } catch (DatabaseQueryExecuteException $e) {
            // TODO maybe we should use select table from information schema instead of this
            return null;
        }
        $migrationTable = new \Phoenix\Database\Element\MigrationTable($table);
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
        return $migrationTable;
    }

    protected function createRealValue($value)
    {
        return is_array($value) ? implode(',', $value) : $value;
    }
}
