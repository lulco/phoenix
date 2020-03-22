<?php

namespace Phoenix\Command;

use Dumper\Dumper;
use Dumper\Indenter;
use Comparator\StructureComparator;
use Phoenix\Database\Adapter\AdapterFactory;
use Phoenix\Database\Element\Structure;
use Phoenix\Exception\InvalidArgumentValueException;
use Phoenix\Exception\PhoenixException;
use Phoenix\Migration\MigrationNameCreator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class DiffCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this->setName('diff')
            ->setDescription('Makes diff of source and target database')
            ->addArgument('source', InputArgument::REQUIRED, 'Source environment from config')
            ->addArgument('target', InputArgument::REQUIRED, 'Target environment from config')
            ->addOption('ignore-tables', null, InputOption::VALUE_REQUIRED, 'Comma separated list of tables to ignore.', 'phoenix_log')
            ->addOption('indent', 'i', InputOption::VALUE_REQUIRED, 'Indentation. Available values: 2spaces, 3spaces, 4spaces, 5spaces, tab', '4spaces')
            ->addOption('migration', null, InputOption::VALUE_REQUIRED, 'Name of migration', 'Initialization')
            ->addOption('dir', null, InputOption::VALUE_REQUIRED, 'Directory to create migration in')
            ->addOption('template', null, InputOption::VALUE_REQUIRED, 'Path to template')
        ;

        parent::configure();
    }

    protected function runCommand(): void
    {
        $source = $this->input->getArgument('source');
        $sourceConfig = $this->config->getEnvironmentConfig($source);
        if (!$sourceConfig) {
            throw new InvalidArgumentValueException('Source "' . $source . '" doesn\'t exist in config');
        }

        $target = $this->input->getArgument('target');
        $targetConfig = $this->config->getEnvironmentConfig($target);
        if (!$targetConfig) {
            throw new InvalidArgumentValueException('Target "' . $target . '" doesn\'t exist in config');
        }

        $templatePath = $this->input->getOption('template') ?: __DIR__ . '/../Templates/DefaultTemplate.phoenix';
        if (!is_file($templatePath)) {
            throw new PhoenixException('Template "' . $templatePath . '" not found');
        }

        $sourceAdapter = AdapterFactory::instance($sourceConfig);
        $sourceStructure = $sourceAdapter->getStructure();

        $targetAdapter = AdapterFactory::instance($targetConfig);
        $targetStructure = $targetAdapter->getStructure();

        $indenter = new Indenter();
        $indent = $indenter->indent($this->input->getOption('indent'));
        $dumper = new Dumper($indent, 2);

        $upTables = $this->getFilteredTables($sourceStructure, $targetStructure);
        $upParts = [];
        $upParts[] = $dumper->dumpTables($upTables);

        $upParts[] = $dumper->dumpForeignKeys($upTables);
        $up = implode("\n\n", array_filter($upParts, function ($upPart) {
            return (bool) $upPart;
        }));

        $downTables = $this->getFilteredTables($targetStructure, $sourceStructure);
        $downParts = [];
        $downParts[] = $dumper->dumpForeignKeys($downTables);
        $downParts[] = $dumper->dumpTables($downTables);
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

    private function getFilteredTables(Structure $sourceStructure, Structure $targetStructure): array
    {
        $ignoredTables = array_filter(array_map('trim', explode(',', $this->input->getOption('ignore-tables'))));
        $structureComparator = new StructureComparator();
        $tables = [];
        $diffTables = $structureComparator->diff($sourceStructure, $targetStructure);
        foreach ($diffTables as $diffTable) {
            if (in_array($diffTable->getName(), $ignoredTables, true)) {
                continue;
            }
            $tables[] = $diffTable;
        }
        return $tables;
    }
}
