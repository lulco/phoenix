<?php

namespace Phoenix\Command;

use Phoenix\Exception\InvalidArgumentValueException;
use Phoenix\Migration\AbstractMigration;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractRunCommand extends AbstractCommand
{
    protected $noMigrationsFoundMessage = '';

    protected $migrationInfoPrefix = '';

    protected function configure(): void
    {
        parent::configure();
        $this->addOption('dry', null, InputOption::VALUE_NONE, 'Only print queries, no execution');
    }

    protected function runCommand(): void
    {
        $dry = (bool) $this->input->getOption('dry');
        if ($dry) {
            $this->output->setVerbosity(OutputInterface::VERBOSITY_DEBUG);
        }

        $migrations = $this->findMigrations();
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
            if ($this->output->getVerbosity() === OutputInterface::VERBOSITY_DEBUG) {
                $executedMigration['executed_queries'] = $executedQueries;
            }
            $executedMigrations[] = $executedMigration;
        }
        $this->outputData['executed_migrations'] = $executedMigrations;
    }

    protected function checkDirs(array $dirs): void
    {
        if (empty($dirs)) {
            return;
        }
        $migrationDirs = $this->config->getMigrationDirs();
        foreach ($dirs as $dir) {
            if (!array_key_exists($dir, $migrationDirs)) {
                throw new InvalidArgumentValueException('Directory "' . $dir . '" doesn\'t exist');
            }
        }
    }

    abstract protected function findMigrations(): array;

    abstract protected function runMigration(AbstractMigration $migration, bool $dry = false): void;
}
