<?php

declare(strict_types=1);

namespace Phoenix\Command;

use Phoenix\Comparator\StructureComparator;
use Phoenix\Database\Element\MigrationTable;
use Phoenix\Database\Element\Structure;
use Phoenix\Dumper\Dumper;
use Phoenix\Dumper\Indenter;
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
            ->addOption('indent', 'i', InputOption::VALUE_REQUIRED, 'Indentation. Available values: 2spaces, 3spaces, 4spaces, 5spaces, tab')
            ->addOption('migration', null, InputOption::VALUE_REQUIRED, 'Name of migration', $this->migrationDefaultName())
            ->addOption('dir', null, InputOption::VALUE_REQUIRED, 'Directory to create migration in')
            ->addOption('template', null, InputOption::VALUE_REQUIRED, 'Path to template')
        ;

        parent::configure();
    }

    protected function runCommand(): void
    {
        $indenter = new Indenter();
        /** @var string $indentOption */
        $indentOption = $this->input->getOption('indent') ?: $this->getConfig()->getIndent();
        $indent = $indenter->indent($indentOption);
        $dumper = $this->createDumper($indent);

        /** @var string $migration */
        $migration = $this->input->getOption('migration') ?: $this->migrationDefaultName();
        /** @var string $template */
        $template = $this->input->getOption('template') ?: $this->getConfig()->getTemplate();
        $migrationCreator = new MigrationCreator($migration, $indent, $template);

        $sourceStructure = $this->sourceStructure();
        $targetStructure = $this->targetStructure();

        $up = $this->createUpDown($sourceStructure, $targetStructure, $dumper, 'up');
        $down = $this->createUpDown($targetStructure, $sourceStructure, $dumper, 'down');

        /** @var string|null $dir */
        $dir = $this->input->getOption('dir');
        $migrationDir = $this->chooseMigrationDir($dir);
        $migrationPath = $migrationCreator->create($up, $down, $migrationDir);

        $this->writeln(['', '<info>Migration "' . $migration . '" created in "' . $migrationPath . '"</info>']);
        $this->outputData['migration_name'] = $migration;
        $this->outputData['migration_filepath'] = $migrationPath;
    }

    abstract protected function migrationDefaultName(): string;

    abstract protected function createDumper(string $indent): Dumper;

    abstract protected function sourceStructure(): Structure;

    abstract protected function targetStructure(): Structure;

    /**
     * @param MigrationTable[] $tables
     * @return array<string, array<string, mixed>>
     */
    abstract protected function loadData(array $tables): array;

    /**
     * @param Structure $sourceStructure
     * @param Structure $targetStructure
     * @return MigrationTable[]
     */
    private function getFilteredTables(Structure $sourceStructure, Structure $targetStructure): array
    {
        /** @var string|null $ignoredTablesOption */
        $ignoredTablesOption = $this->input->getOption('ignore-tables');
        $ignoredTables = $ignoredTablesOption ? array_filter(array_map('trim', explode(',', $ignoredTablesOption))) : [];
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

    private function createUpDown(Structure $sourceStructure, Structure $targetStructure, Dumper $dumper, string $dumpType): string
    {
        $tables = $this->getFilteredTables($sourceStructure, $targetStructure);
        $parts = [];
        if ($dumpType === 'down') {
            $parts[] = $dumper->dumpForeignKeys($tables);
        }
        $parts[] = $dumper->dumpTables($tables, $dumpType);
        if ($dumpType === 'up') {
            $parts[] = $dumper->dumpDataUp($this->loadData($tables));
            $parts[] = $dumper->dumpForeignKeys($tables);
        }
        return implode("\n\n", array_filter($parts));
    }

    private function chooseMigrationDir(?string $dir): string
    {
        try {
            return $this->getConfig()->getMigrationDir($dir);
        } catch (InvalidArgumentValueException $e) {
            $symfonyStyle = new SymfonyStyle($this->input, $this->output);
            $dir = $symfonyStyle->choice($e->getMessage(), $this->getConfig()->getMigrationDirs());
            return $this->chooseMigrationDir($dir);
        }
    }
}
