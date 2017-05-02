<?php

namespace Phoenix\Command;

use Dumper\Dumper;
use Phoenix\Command\AbstractCommand;
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
        $dumper = new Dumper($indent);

        $tables = $this->getFilteredTables($ignoredTables);
        $migration = $dumper->dumpTables($tables);

        if ($input->getOption('data')) {
            $data = $this->loadData($tables);
            $migration .= $dumper->dumpData($data);
        }

        $output->write($migration);
        $output->writeln('');
    }

    private function getFilteredTables(array $ignoredTables = [])
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

    private function loadData(array $tables = [])
    {
        $ignoredDataTables = $this->input->getOption('ignore-data-tables')
            ? array_map('trim', explode(',', $this->input->getOption('ignore-data-tables')))
            : [];

        $data = [];
        foreach ($tables as $table) {
            if (in_array($table->getName(), $ignoredDataTables)) {
                continue;
            }
            $rows = $this->adapter->fetchAll($table->getName());
            if (empty($rows)) {
                continue;
            }
            $data[$table->getName()] = $rows;
        }
        return $data;
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
