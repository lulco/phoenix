<?php

namespace Phoenix\Command;

use Phoenix\Migration\AbstractMigration;
use Phoenix\Migration\Manager;
use Symfony\Component\Console\Input\InputOption;

class MigrateCommand extends AbstractRunCommand
{
    protected $noMigrationsFoundMessage = 'Nothing to migrate';

    protected $migrationInfoPrefix = 'Migration';

    protected function configure()
    {
        parent::configure();
        $this->setName('migrate')
            ->addOption('first', null, InputOption::VALUE_NONE, 'Run only first migrations')
            ->addOption('dir', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Directory to migrate')
            ->addOption('class', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Class to migrate')
            ->setDescription('Run migrations');
    }

    protected function findMigrations()
    {
        $target = $this->input->getOption('first') ? Manager::TARGET_FIRST : Manager::TARGET_ALL;
        $dirs = $this->input->getOption('dir') ?: [];
        $this->checkDirs($dirs);
        $classes = $this->input->getOption('class') ?: [];
        return $this->manager->findMigrationsToExecute(Manager::TYPE_UP, $target, $dirs, $classes);
    }

    protected function runMigration(AbstractMigration $migration, $dry = false)
    {
        $migration->migrate($dry);
        if (!$dry) {
            $this->manager->logExecution($migration);
        }
    }
}
