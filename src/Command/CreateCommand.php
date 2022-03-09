<?php

declare(strict_types=1);

namespace Phoenix\Command;

use Phoenix\Dumper\Indenter;
use Phoenix\Exception\InvalidArgumentValueException;
use Phoenix\Migration\MigrationCreator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;

final class CreateCommand extends AbstractCommand
{
    public function __construct(string $name = 'create')
    {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        parent::configure();
        $this->setDescription('Create migration')
            ->addArgument('migration', InputArgument::REQUIRED, 'Name of migration')
            ->addArgument('dir', InputArgument::OPTIONAL, 'Directory to create migration in')
            ->addOption('template', null, InputOption::VALUE_REQUIRED, 'Path to template')
            ->addOption('indent', 'i', InputOption::VALUE_REQUIRED, 'Indentation. Available values: 2spaces, 3spaces, 4spaces, 5spaces, tab');
    }

    protected function runCommand(): void
    {
        $indenter = new Indenter();
        /** @var string $indentOption */
        $indentOption = $this->input->getOption('indent') ?: $this->getConfig()->getIndent();
        $indent = $indenter->indent($indentOption);

        /** @var string $migration */
        $migration = $this->input->getArgument('migration');
        /** @var string $template */
        $template = $this->input->getOption('template') ?: $this->getConfig()->getTemplate();
        $migrationCreator = new MigrationCreator($migration, $indent, $template);

        /** @var string|null $dir */
        $dir = $this->input->getArgument('dir');
        $migrationDir = $this->chooseMigrationDir($dir);
        $migrationPath = $migrationCreator->create(str_repeat($indent, 2), str_repeat($indent, 2), $migrationDir);

        $this->writeln(['', '<info>Migration "' . $migration . '" created in "' . $migrationPath . '"</info>']);

        $this->outputData['migration_name'] = $migration;
        $this->outputData['migration_filepath'] = $migrationPath;
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
