<?php

namespace Phoenix\Command;

use Phoenix\Command\AbstractCommand;
use Phoenix\Migration\Init\Init;
use Phoenix\Migration\Manager;
use Symfony\Component\Console\Output\OutputInterface;

class CleanupCommand extends AbstractCommand
{
    protected function configure()
    {
        $this->setName('cleanup')
            ->setDescription('Rollback all migrations and delete log table');
        parent::configure();
    }

    protected function runCommand()
    {
        $migrations = $this->manager->findMigrationsToExecute(Manager::TYPE_DOWN);
        $executedMigrations = [];
        foreach ($migrations as $migration) {
            $migration->rollback();
            $this->manager->removeExecution($migration);

            $this->writeln('');
            $this->writeln('<info>Rollback for migration ' . $migration->getClassName() . ' executed</info>');
            $executedMigrations[] = $this->addMigrationToList($migration, $this->output);
        }

        $filename = __DIR__ . '/../Migration/Init/0_init.php';
        require_once $filename;
        $migration = new Init($this->adapter, $this->config->getLogTableName());
        $migration->rollback();

        $this->writeln('');
        $this->writeln('<info>Phoenix cleaned</info>');
        $this->outputData['message'] = 'Phoenix cleaned';
        $executedMigrations[] = $this->addMigrationToList($migration, $this->output);
        $this->writeln('');

        if ($this->output->getVerbosity() === OutputInterface::VERBOSITY_DEBUG) {
            $this->outputData['executed_migrations'] = $executedMigrations;
        }
    }

    private function addMigrationToList($migration)
    {
        $executedQueries = $migration->getExecutedQueries();
        $this->writeln('Executed queries:', OutputInterface::VERBOSITY_DEBUG);
        $this->writeln($executedQueries, OutputInterface::VERBOSITY_DEBUG);

        $executedMigration = [
            'classname' => $migration->getClassName(),
        ];
        if ($this->output->getVerbosity() === OutputInterface::VERBOSITY_DEBUG) {
            $executedMigration['executed_queries'] = $executedQueries;
        }
        return $executedMigration;
    }
}
