<?php

namespace Phoenix\Command;

use Phoenix\Migration\AbstractMigration;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractRunCommand extends AbstractCommand
{
    protected $noMigrationsFoundMessage = '';

    protected $migrationInfoPrefix = '';

    protected function runCommand(InputInterface $input, OutputInterface $output)
    {
        $migrations = $this->findMigrations($input);
        if (empty($migrations)) {
            $output->writeln('');
            $output->writeln('<info>' . $this->noMigrationsFoundMessage . '</info>');
            return;
        }
        
        foreach ($migrations as $migration) {
            $output->writeln('');
            $output->writeln('<info>' . $this->migrationInfoPrefix . ' ' . $migration->getClassName() . ' executing</info>');

            $start = microtime(true);
            $this->runMigration($migration);
            $output->writeln('<info>' . $this->migrationInfoPrefix . ' ' . $migration->getClassName() . ' executed</info>. <comment>Took ' . sprintf('%.4fs', microtime(true) - $start) . '</comment>');

            $output->writeln('Executed queries:', OutputInterface::VERBOSITY_DEBUG);
            $output->writeln($migration->getExecutedQueries(), OutputInterface::VERBOSITY_DEBUG);
        }
    }

    abstract protected function findMigrations(InputInterface $input);

    abstract protected function runMigration(AbstractMigration $migration);
}
