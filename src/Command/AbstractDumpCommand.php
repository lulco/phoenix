<?php

namespace Phoenix\Command;

use Comparator\StructureComparator;
use Dumper\Dumper;
use Dumper\Indenter;
use Phoenix\Database\Element\MigrationTable;
use Phoenix\Database\Element\Structure;
use Phoenix\Exception\InvalidArgumentValueException;
use Phoenix\Migration\MigrationCreator;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class AbstractDumpCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this
            ->addOption('ignore-tables', null, InputOption::VALUE_REQUIRED, 'Comma separated list of tables to ignore (Structure and data).', 'phoenix_log')
            ->addOption('indent', 'i', InputOption::VALUE_REQUIRED, 'Indentation. Available values: 2spaces, 3spaces, 4spaces, 5spaces, tab', '4spaces')
            ->addOption('migration', null, InputOption::VALUE_REQUIRED, 'Name of migration', 'Initialization')
            ->addOption('dir', null, InputOption::VALUE_REQUIRED, 'Directory to create migration in')
            ->addOption('template', null, InputOption::VALUE_REQUIRED, 'Path to template')
        ;

        parent::configure();
    }

    protected function runCommand(): void
    {
        $indenter = new Indenter();
        $indent = $indenter->indent($this->input->getOption('indent'));
        $dumper = new Dumper($indent, 2);

        $migration = $this->input->getOption('migration') ?: 'Initialization';
        $migrationCreator = new MigrationCreator($migration, $indent, $this->input->getOption('template'));

        $sourceStructure = $this->sourceStructure();
        $targetStructure = $this->targetStructure();

        $up = $this->createUpDown($sourceStructure, $targetStructure, $dumper, 'up', $this->shouldLoadData());
        $down = $this->createUpDown($targetStructure, $sourceStructure, $dumper, 'down');

        $dir = $this->input->getOption('dir');
        $migrationDir = $this->chooseMigrationDir($dir);
        $migrationPath = $migrationCreator->create($up, $down, $migrationDir);

        $this->writeln('');
        $this->writeln('<info>Migration "' . $migration . '" created in "' . $migrationPath . '"</info>');
        $this->outputData['migration_name'] = $migration;
        $this->outputData['migration_filepath'] = $migrationPath;
    }

    abstract protected function sourceStructure(): Structure;

    abstract protected function targetStructure(): Structure;

    abstract protected function shouldLoadData(): bool;

    abstract protected function loadData(array $tables): array;

    /**
     * @param Structure $sourceStructure
     * @param Structure $targetStructure
     * @return MigrationTable[]
     */
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

    private function createUpDown(Structure $sourceStructure, Structure $targetStructure, Dumper $dumper, string $type, bool $loadData = false): string
    {
        $tables = $this->getFilteredTables($sourceStructure, $targetStructure);
        $parts = [];
        if ($type === 'down') {
            $parts[] = $dumper->dumpForeignKeys($tables);
        }
        $parts[] = $dumper->dumpTables($tables);
        if ($loadData) {
            $data = $this->loadData($tables);
            $parts[] = $dumper->dumpDataUp($data);
        }
        if ($type === 'up') {
            $parts[] = $dumper->dumpForeignKeys($tables);
        }
        return implode("\n\n", array_filter($parts));
    }

    private function chooseMigrationDir(?string $dir): string
    {
        try {
            return $this->config->getMigrationDir($dir);
        } catch (InvalidArgumentValueException $e) {
            $symfonyStyle = new SymfonyStyle($this->input, $this->output);
            $dir = $symfonyStyle->choice($e->getMessage(), $this->config->getMigrationDirs());
            return $this->chooseMigrationDir($dir);
        }
    }
}
