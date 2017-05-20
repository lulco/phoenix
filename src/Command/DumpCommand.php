<?php

namespace Phoenix\Command;

use Dumper\Dumper;
use Phoenix\Command\AbstractCommand;
use Phoenix\Exception\PhoenixException;
use Phoenix\Migration\MigrationNameCreator;
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
            ->addOption('ignore-tables', null, InputOption::VALUE_REQUIRED, 'Comma separaterd list of tables to ignore (Structure and data). Default: phoenix_log')
            ->addOption('ignore-data-tables', null, InputOption::VALUE_REQUIRED, 'Comma separaterd list of tables which will be exported without data (Option -d, --data is required to use this option)')
            ->addOption('indent', 'i', InputOption::VALUE_REQUIRED, 'Indentation. Available values: 2spaces, 3spaces, 4spaces, 5spaces, tab', '4spaces')
            ->addOption('migration', null, InputOption::VALUE_REQUIRED, 'Name of migration', 'Initialization')
            ->addOption('dir', null, InputOption::VALUE_REQUIRED, 'Directory to create migration in')
            ->addOption('template', null, InputOption::VALUE_REQUIRED, 'Path to template')
        ;

        parent::configure();
    }

    protected function runCommand(InputInterface $input, OutputInterface $output)
    {
        $ignoredTables = array_map('trim', explode(',', $input->getOption('ignore-tables') . ',' . $this->config->getLogTableName() ? : $this->config->getLogTableName()));
        $output->writeln('');

        $indent = $this->getIndent($input);
        $dumper = new Dumper($indent, 2);

        $tables = $this->getFilteredTables($ignoredTables);
        $upParts = [];
        $upParts[] = $dumper->dumpTablesUp($tables);

        if ($input->getOption('data')) {
            $data = $this->loadData($tables);
            $upParts[] = $dumper->dumpDataUp($data);
        }
        $upParts[] = $dumper->dumpForeignKeysUp($tables);
        $up = implode("\n\n", array_filter($upParts, function ($upPart) {
            return (bool) $upPart;
        }));

        $downParts = [];
        $downParts[] = $dumper->dumpForeignKeysDown($tables);
        $downParts[] = $dumper->dumpTablesDown($tables);
        $down = implode("\n\n", array_filter($downParts, function ($downPart) {
            return (bool) $downPart;
        }));

        $migration = $input->getOption('migration') ?: 'Initialization';
        $migrationNameCreator = new MigrationNameCreator($migration);
        $filename = $migrationNameCreator->getFileName();
        $dir = $input->getOption('dir');
        $migrationDir = $this->config->getMigrationDir($dir);

        $templatePath = $input->getOption('template') ?: __DIR__ . '/../Templates/DefaultTemplate.phoenix';
        if (!file_exists($templatePath)) {
            throw new PhoenixException('Template "' . $templatePath . '" not found');
        }

        $template = file_get_contents($templatePath);
        $namespace = '';
        if ($migrationNameCreator->getNamespace()) {
            $namespace .= "namespace {$migrationNameCreator->getNamespace()};\n\n";
        }
        $template = str_replace('###NAMESPACE###', $namespace, $template);
        $template = str_replace('###CLASSNAME###', $migrationNameCreator->getClassName(), $template);
        $template = str_replace('###INDENT###', $indent, $template);
        $template = str_replace('###UP###', $up, $template);
        $template = str_replace('###DOWN###', $down, $template);

        $migrationPath = $migrationDir . '/' . $filename;
        file_put_contents($migrationPath, $template);
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
