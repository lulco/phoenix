<?php

declare(strict_types=1);

namespace Phoenix\Command;

use Phoenix\Migration\AbstractMigration;
use Phoenix\Migration\Manager;
use Symfony\Component\Console\Input\InputOption;

final class MigrateCommand extends AbstractRunCommand
{
    protected string $noMigrationsFoundMessage = 'Nothing to migrate';

    protected string $migrationInfoPrefix = 'Migration';

    public function __construct(string $name = 'migrate')
    {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        parent::configure();
        $this->addOption('first', null, InputOption::VALUE_NONE, 'Run only first migrations')
            ->addOption('target', null, InputOption::VALUE_REQUIRED, 'Datetime of last migration which should be executed')
            ->addOption('dir', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Directory to migrate', [])
            ->addOption('class', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Class to migrate', [])
            ->setDescription('Run migrations');
    }

    protected function findMigrations(): array
    {
        /** @var string|null $targetOption */
        $targetOption = $this->input->getOption('target');
        /** @var bool $first */
        $first = $this->input->getOption('first');
        $target = $targetOption ? str_pad($targetOption, 14, '0', STR_PAD_RIGHT) : ($first ? Manager::TARGET_FIRST : Manager::TARGET_ALL);
        /** @var string[] $dirs */
        $dirs = $this->input->getOption('dir') ?: [];
        $this->checkDirs($dirs);
        /** @var string[] $classes */
        $classes = $this->input->getOption('class') ?: [];
        return $this->manager->findMigrationsToExecute(Manager::TYPE_UP, $target, $dirs, $classes);
    }

    protected function runMigration(AbstractMigration $migration, bool $dry = false): void
    {
        $migration->migrate($dry);
        if (!$dry) {
            $this->manager->logExecution($migration);
        }
    }
}
