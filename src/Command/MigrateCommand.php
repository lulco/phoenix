<?php

namespace Phoenix\Command;

use Phoenix\Migration\AbstractMigration;
use Phoenix\Migration\Manager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateCommand extends AbstractRunCommand
{
    protected $noMigrationsFoundMessage = 'Nothing to migrate';

    protected $migrationInfoPrefix = 'Migration';

    protected function configure()
    {
        $this->setName('migrate')
            ->addOption('first', null, InputOption::VALUE_NONE, 'Run only first migrations')
            ->setDescription('Run migrations');
        parent::configure();
    }

    protected function findMigrations(InputInterface $input)
    {
        $target = $input->getOption('first') ? Manager::TARGET_FIRST : Manager::TARGET_ALL;
        return $this->manager->findMigrationsToExecute(Manager::TYPE_UP, $target);
    }

    protected function runMigration(AbstractMigration $migration)
    {
        $migration->migrate();
        $this->manager->logExecution($migration);
    }
}
