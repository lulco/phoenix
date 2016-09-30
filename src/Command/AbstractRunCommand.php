<?php

namespace Phoenix\Command;

use Phoenix\Migration\AbstractMigration;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractRunCommand extends AbstractCommand
{
    protected $noMigrationsFoundMessage = '';

    protected $migrationInfoPrefix = '';

    protected function configure()
    {
		parent::configure();
        $this->addOption('dry', null, InputOption::VALUE_NONE, 'Only print queries, no execution');
    }

    protected function runCommand(InputInterface $input, OutputInterface $output)
    {
        $migrations = $this->findMigrations($input);
        if (empty($migrations)) {
            $output->writeln('');
            $output->writeln('<info>' . $this->noMigrationsFoundMessage . '</info>');
            return;
        }

        $dry = (bool) $input->getOption('dry');
        foreach ($migrations as $migration) {
            $output->writeln('');
            $output->writeln('<info>' . $this->migrationInfoPrefix . ' ' . $migration->getClassName() . ' executing</info>');
            
            $start = microtime(true);
            $this->runMigration($migration, $dry);
            $output->writeln('<info>' . $this->migrationInfoPrefix . ' ' . $migration->getClassName() . ' executed</info>. <comment>Took ' . sprintf('%.4fs', microtime(true) - $start) . '</comment>');

            $output->writeln('Executed queries:', $dry ? 0 : OutputInterface::VERBOSITY_DEBUG);
            $output->writeln($migration->getExecutedQueries(), $dry ? 0 : OutputInterface::VERBOSITY_DEBUG);
        }
    }

    abstract protected function findMigrations(InputInterface $input);

    abstract protected function runMigration(AbstractMigration $migration, $dry = false);
}
