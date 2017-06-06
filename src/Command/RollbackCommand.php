<?php

namespace Phoenix\Command;

use Phoenix\Migration\AbstractMigration;
use Phoenix\Migration\Manager;
use Symfony\Component\Console\Input\InputOption;

class RollbackCommand extends AbstractRunCommand
{
    protected $noMigrationsFoundMessage = 'Nothing to rollback';

    protected $migrationInfoPrefix = 'Rollback for migration';

    protected function configure()
    {
        parent::configure();
        $this->setName('rollback')
            ->addOption('all', null, InputOption::VALUE_NONE, 'Rollback all migrations')
            ->setDescription('Rollback migrations');
    }

    protected function findMigrations()
    {
        $target = $this->input->getOption('all') ? Manager::TARGET_ALL : Manager::TARGET_FIRST;
        return $this->manager->findMigrationsToExecute(Manager::TYPE_DOWN, $target);
    }

    protected function runMigration(AbstractMigration $migration, $dry = false)
    {
        $migration->rollback($dry);
        if (!$dry) {
            $this->manager->removeExecution($migration);
        }
    }
}
