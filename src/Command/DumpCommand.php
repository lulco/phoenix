<?php

namespace Phoenix\Command;

use Dumper\Dumper;
use Dumper\Indenter;
use Phoenix\Exception\PhoenixException;
use Phoenix\Migration\MigrationNameCreator;
use Symfony\Component\Console\Input\InputOption;

class DumpCommand extends AbstractCommand
{
    protected function configure(): void
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

    protected function runCommand(): void
    {
        $ignoredTables = array_map('trim', explode(',', $this->input->getOption('ignore-tables') . ',' . $this->config->getLogTableName() ? : $this->config->getLogTableName()));

        $templatePath = $this->input->getOption('template') ?: __DIR__ . '/../Templates/DefaultTemplate.phoenix';
        if (!is_file($templatePath)) {
            throw new PhoenixException('Template "' . $templatePath . '" not found');
        }

        $indenter = new Indenter();
        $indent = $indenter->indent($this->input->getOption('indent'));
        $dumper = new Dumper($indent, 2);

        $tables = $this->getFilteredTables($ignoredTables);
        $upParts = [];
        $upParts[] = $dumper->dumpTablesUp($tables);

        if ($this->input->getOption('data')) {
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

        $migration = $this->input->getOption('migration') ?: 'Initialization';
        $migrationNameCreator = new MigrationNameCreator($migration);
        $filename = $migrationNameCreator->getFileName();
        $dir = $this->input->getOption('dir');
        $migrationDir = $this->config->getMigrationDir($dir);

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
        $migrationPath = realpath($migrationPath);

        $this->writeln('');
        $this->writeln('<info>Migration "' . $migration . '" created in "' . $migrationPath . '"</info>');
        $this->outputData['migration_name'] = $migration;
        $this->outputData['migration_filepath'] = $migrationPath;
    }

    private function getFilteredTables(array $ignoredTables = []): array
    {
        $tables = [];
        $structure = $this->adapter->getStructure();
        foreach ($structure->getTables() as $table) {
            if (in_array($table->getName(), $ignoredTables, true)) {
                continue;
            }
            $tables[] = $table;
        }
        return $tables;
    }

    private function loadData(array $tables = []): array
    {
        $ignoredDataTables = $this->input->getOption('ignore-data-tables')
            ? array_map('trim', explode(',', $this->input->getOption('ignore-data-tables')))
            : [];

        $data = [];
        foreach ($tables as $table) {
            if (in_array($table->getName(), $ignoredDataTables, true)) {
                continue;
            }
            $data[$table->getName()] = $this->adapter->fetchAll($table->getName());
        }
        return $data;
    }
}
