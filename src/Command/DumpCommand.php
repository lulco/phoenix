<?php

namespace Phoenix\Command;

use Phoenix\Command\AbstractCommand;
use Phoenix\Migration\Manager;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DumpCommand extends AbstractCommand
{
    protected function configure()
    {
        $this->setName('dump')
            ->setDescription('Dump actual database structure to migration file');
        parent::configure();
    }

    protected function runCommand(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('');

        $indent = '    ';

        $migration = '';
        $tables = $this->getOrderedTables();
        foreach ($tables as $table) {
            $migration .= $indent . "\$this->table('{$table->getName()}')\n";
            foreach ($table->getColumns() as $column) {
                $migration .= "$indent$indent" . "->addColumn('{$column->getName()}', '{$column->getType()}')\n";
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

        $output->write($migration);

        $output->writeln('');
    }

    private function getOrderedTables()
    {
        $tables = [];
        $structure = $this->adapter->getStructure();
        foreach ($structure->getTables() as $table) {
            if ($table->getName() == $this->config->getLogTableName()) {
                continue;
            }
            $tables[] = $table;
        }
        return $tables;
    }

    private function columnsToString(array $columns)
    {
        $columns = array_map(function ($column) {
            return "'" . $column . "'";
        }, $columns);
        $implodedColumns = implode(', ', $columns);
        return count($columns) > 1 ? '[' . $implodedColumns . ']' : $implodedColumns;
    }
}
