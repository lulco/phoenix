<?php

namespace Phoenix\Command;

use Phoenix\Migration\AbstractMigration;
use Phoenix\Migration\Manager;
use Symfony\Component\Console\Input\InputOption;

class RollbackCommand extends AbstractRunCommand
{
    protected $noMigrationsFoundMessage = 'Nothing to rollback';

    protected $migrationInfoPrefix = 'Rollback for migration';

    protected function configure(): void
    {
        parent::configure();
        $this->setName('rollback')
            ->addOption('all', null, InputOption::VALUE_NONE, 'Rollback all migrations')
            ->addOption('dir', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Directory to rollback')
            ->addOption('class', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Class to rollback')
            ->setDescription('Rollback migrations');
    }

    protected function findMigrations(): array
    {
        $target = $this->input->getOption('all') ? Manager::TARGET_ALL : Manager::TARGET_FIRST;
        $dirs = $this->input->getOption('dir') ?: [];
        $this->checkDirs($dirs);
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
