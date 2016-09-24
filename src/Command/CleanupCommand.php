<?php

namespace Phoenix\Command;

use Phoenix\Command\AbstractCommand;
use Phoenix\Migration\Init\Init;
use Phoenix\Migration\Manager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CleanupCommand extends AbstractCommand
{
    protected function configure()
    {
        $this->setName('cleanup')
            ->setDescription('Rollback all migrations and delete log table');
        parent::configure();
    }

    protected function runCommand(InputInterface $input, OutputInterface $output)
    {
        $migrations = $this->manager->findMigrationsToExecute(Manager::TYPE_DOWN);
        if (empty($migrations)) {
            $output->writeln('');
            $output->writeln('<info>Nothing to rollback</info>');
            $output->writeln('');
        }

        foreach ($migrations as $migration) {
            $migration->rollback();
            $this->manager->removeExecution($migration);

            $output->writeln('');
            $output->writeln('<info>Rollback for migration ' . $migration->getClassName() . ' executed</info>');
            $output->writeln('Executed queries:', OutputInterface::VERBOSITY_DEBUG);
            $output->writeln($migration->getExecutedQueries(), OutputInterface::VERBOSITY_DEBUG);
        }

        $filename = __DIR__ . '/../Migration/Init/0_init.php';
        require_once $filename;
        $migration = new Init($this->adapter, $this->config->getLogTableName());
        $migration->rollback();

        $output->writeln('');
        $output->writeln('<info>Phoenix cleaned</info>');
        $output->writeln('Executed queries:', OutputInterface::VERBOSITY_DEBUG);
        $output->writeln($migration->getExecutedQueries(), OutputInterface::VERBOSITY_DEBUG);
        $output->writeln('');
    }
}
