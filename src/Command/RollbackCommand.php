<?php

namespace Phoenix\Command;

use Phoenix\Migration\AbstractMigration;
use Phoenix\Migration\Manager;
use Symfony\Component\Console\Input\InputOption;

class RollbackCommand extends AbstractRunCommand
{
    /** @var string */
    protected $noMigrationsFoundMessage = 'Nothing to rollback';

    /** @var string */
    protected $migrationInfoPrefix = 'Rollback for migration';

    protected function configure(): void
    {
        parent::configure();
        $this->setName('rollback')
            ->addOption('all', null, InputOption::VALUE_NONE, 'Rollback all migrations')
            ->addOption('target', null, InputOption::VALUE_REQUIRED, 'Datetime of last migration which should be rollbacked')
            ->addOption('dir', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Directory to rollback', [])
            ->addOption('class', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Class to rollback', [])
            ->setDescription('Rollback migrations');
    }

    protected function findMigrations(): array
    {
        /** @var string|null $targetOption */
        $targetOption = $this->input->getOption('target');
        $target = $targetOption ? str_pad($targetOption, 14, '0', STR_PAD_RIGHT) : ($this->input->getOption('all') ? Manager::TARGET_ALL : Manager::TARGET_FIRST);
        /** @var string[] $dirs */
        $dirs = $this->input->getOption('dir') ?: [];
        $this->checkDirs($dirs);
        /** @var string[] $classes */
        $classes = $this->input->getOption('class') ?: [];
        return $this->manager->findMigrationsToExecute(Manager::TYPE_DOWN, $target, $dirs, $classes);
    }

    protected function runMigration(AbstractMigration $migration, bool $dry = false): void
    {
        $migration->rollback($dry);
        if (!$dry) {
            $this->manager->removeExecution($migration);
        }
    }
}
