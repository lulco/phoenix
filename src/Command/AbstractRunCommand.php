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
        $this
            ->addOption('dry', null, InputOption::VALUE_NONE, 'Only print queries, no execution');
    }

    protected function runCommand(InputInterface $input, OutputInterface $output)
    {
        $dry = (bool) $input->getOption('dry');
        if ($dry) {
            $output->setVerbosity(OutputInterface::VERBOSITY_DEBUG);
        }

        $migrations = $this->findMigrations($input);
        if (empty($migrations)) {
            $this->writeln('');
            $this->writeln('<info>' . $this->noMigrationsFoundMessage . '</info>');
        }

        $executedMigrations = [];
        foreach ($migrations as $migration) {
            $this->writeln('');
            $this->writeln('<info>' . $this->migrationInfoPrefix . ' ' . $migration->getClassName() . ' executing</info>');

            $start = microtime(true);
            $this->runMigration($migration, $dry);
            $executionTime = microtime(true) - $start;
            $this->writeln('<info>' . $this->migrationInfoPrefix . ' ' . $migration->getClassName() . ' executed</info>. <comment>Took ' . sprintf('%.4fs', $executionTime) . '</comment>');

            $executedQueries = $migration->getExecutedQueries();
            $this->writeln('Executed queries:', OutputInterface::VERBOSITY_DEBUG);
            $this->writeln($executedQueries, OutputInterface::VERBOSITY_DEBUG);

            $executedMigration = [
                'classname' => $migration->getClassName(),
                'execution_time' => $executionTime,
            ];
            if ($output->getVerbosity() === OutputInterface::VERBOSITY_DEBUG) {
                $executedMigration['executed_queries'] = $executedQueries;
            }
            $executedMigrations[] = $executedMigration;
        }
        $this->outputData['executed_migrations'] = $executedMigrations;
    }

    abstract protected function findMigrations(InputInterface $input);

    abstract protected function runMigration(AbstractMigration $migration, $dry = false);
}
