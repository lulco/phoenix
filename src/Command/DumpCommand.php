<?php

namespace Phoenix\Command;

use Phoenix\Command\AbstractCommand;
use Phoenix\Database\Element\Column;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DumpCommand extends AbstractCommand
{
    protected function configure()
    {
        $this->setName('dump')
            ->setDescription('Dump actual database structure to migration file')
            ->addOption('data', 'd', InputOption::VALUE_NONE, 'Dump structure and also data')
            ->addOption('ignore-tables', null, InputOption::VALUE_OPTIONAL, 'Comma separaterd list of tables to ignore (Structure and data). Default: phoenix_log')
            ->addOption('ignore-data-tables', null, InputOption::VALUE_OPTIONAL, 'Comma separaterd list of tables which will be exported without data (Option -d, --data is required to use this option)')
            ->addOption('indent', 'i', InputOption::VALUE_OPTIONAL, 'Indentation. Available values: 2spaces, 3spaces, 4spaces, 5spaces, tab', '4spaces')
        ;

        parent::configure();
    }

    protected function runCommand(InputInterface $input, OutputInterface $output)
    {
        $ignoredTables = array_map('trim', explode(',', $input->getOption('ignore-tables') . ',' . $this->config->getLogTableName() ? : $this->config->getLogTableName()));
        $output->writeln('');

        $indent = $this->getIndent($input);

        $migration = '';
        $tables = $this->getOrderedTables($ignoredTables);
        foreach ($tables as $table) {
            $migration .= $indent . "\$this->table('{$table->getName()}'";
            if ($table->getPrimary()) {
                $migration .= ", " . $this->columnsToString($table->getPrimary());
            }
            $migration .= ")\n";

            foreach ($table->getColumns() as $column) {
                $migration .= "$indent$indent" . "->addColumn('{$column->getName()}', '{$column->getType()}'" . $this->settingsToString($column->getType(), $column->getSettings()) . ")\n";
            }
            foreach ($table->getIndexes() as $index) {
                $migration .= "$indent$indent" . "->addIndex(";
                $indexColumns = $index->getColumns();
                $migration .= $this->columnsToString($indexColumns) . ", '" . strtolower($index->getType()) . "', '" . strtolower($index->getMethod()) . "', '{$index->getName()}')\n";
            }
            foreach ($table->getForeignKeys() as $foreignKey) {
                $migration .= "$indent$indent" . "->addForeignKey(";
                $migration .= $this->columnsToString($foreignKey->getColumns()) . ", '{$foreignKey->getReferencedTable()}', ";
                $migration .= $this->columnsToString($foreignKey->getReferencedColumns()) . ", '{$foreignKey->getOnDelete()}', '{$foreignKey->getOnUpdate()}')\n";
            }
            $migration .= "$indent$indent" . "->create();\n\n";
        }

        if ($input->getOption('data')) {
            $ignoredDataTables = $input->getOption('ignore-data-tables')
                ? array_merge($ignoredTables, array_map('trim', explode(',', $input->getOption('ignore-data-tables'))))
                : $ignoredTables;

            foreach ($tables as $table) {
                if (in_array($table->getName(), $ignoredDataTables)) {
                    continue;
                }
                $rows = $this->adapter->fetchAll($table->getName());
                if (empty($rows)) {
                    continue;
                }
                $migration .= "$indent\$this->insert('{$table->getName()}', [\n";
                foreach ($rows as $row) {
                    $migration .= "$indent$indent" . "[\n";
                    foreach ($row as $column => $value) {
                        $migration .= "$indent$indent$indent'$column' => '" . addslashes($value) . "',\n";
                    }
                    $migration .= "$indent$indent],\n";
                }
                $migration .= "$indent]);\n\n";
            }
        }

        $output->write($migration);

        $output->writeln('');
    }

    private function getOrderedTables(array $ignoredTables = [])
    {
        $tables = [];
        $structure = $this->adapter->getStructure();
        foreach ($structure->getTables() as $table) {
            if (in_array($table->getName(), $ignoredTables)) {
                continue;
            }
            $tables[] = $table;
        }
        return $tables;
    }

    private function columnsToString(array $columns)
    {
        $columns = array_map(function ($column) {
            return "'$column'";
        }, $columns);
        $implodedColumns = implode(', ', $columns);
        return count($columns) > 1 ? '[' . $implodedColumns . ']' : $implodedColumns;
    }

    private function settingsToString($type, array $settings)
    {
        $defaultSettings = [
            'autoincrement' => false,
            'null' => false,
            'default' => null,
            'signed' => true,
            'length' => null,
            'decimals' => null,
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
        foreach ($settings as $setting => $value) {
            if (in_array($setting, ['charset', 'collation'])) {
                continue;
            }
            if ($value === $defaultSettings[$setting]) {
                continue;
            }
            if ($value === null) {
                $value = 'null';
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

    private function getIndent(InputInterface $input)
    {
        $indent = strtolower(str_replace([' ', '-', '_'], '', $input->getOption('indent')));
        if ($indent == '2spaces') {
            return '  ';
        }
        if ($indent == '3spaces') {
            return '   ';
        }
        if ($indent == '5spaces') {
            return '     ';
        }
        if ($indent == 'tab') {
            return "\t";
        }
        return '    ';
    }
}
