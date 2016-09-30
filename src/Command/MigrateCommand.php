<?php

namespace Phoenix\Command;

use Phoenix\Migration\AbstractMigration;
use Phoenix\Migration\Manager;
use Symfony\Component\Console\Input\InputInterface;
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
            ->setDescription('Run migrations');
        
    }

    protected function findMigrations(InputInterface $input)
    {
        $target = $input->getOption('first') ? Manager::TARGET_FIRST : Manager::TARGET_ALL;
        return $this->manager->findMigrationsToExecute(Manager::TYPE_UP, $target);
    }

    protected function runMigration(AbstractMigration $migration, $dry = false)
    {
        $migration->migrate($dry);
        if (!$dry) {
            $this->manager->logExecution($migration);
        }
    }
}
