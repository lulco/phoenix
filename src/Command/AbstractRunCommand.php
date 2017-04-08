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
            ->addOption('dry', null, InputOption::VALUE_NONE, 'Only print queries, no execution')
            ->addOption('output-format', 'f', InputOption::VALUE_REQUIRED, 'Format of the output. Available values: text, json', 'text');
    }

    protected function runCommand(InputInterface $input, OutputInterface $output)
    {
        $dry = (bool) $input->getOption('dry');
        if ($dry) {
            $output->setVerbosity(OutputInterface::VERBOSITY_DEBUG);
        }
        $optionsNormal = $input->getOption('output-format') === 'json' ? -1 : 0;
        $optionsDebug = $input->getOption('output-format') === 'json' ? -1 : OutputInterface::VERBOSITY_DEBUG;

        $migrations = $this->findMigrations($input);
        if (empty($migrations)) {
            $output->writeln('', $optionsNormal);
            $output->writeln('<info>' . $this->noMigrationsFoundMessage . '</info>', $optionsNormal);
        }

        $executedMigrations = [];
        foreach ($migrations as $migration) {
            $output->writeln('', $optionsNormal);
            $output->writeln('<info>' . $this->migrationInfoPrefix . ' ' . $migration->getClassName() . ' executing</info>', $optionsNormal);

            $start = microtime(true);
            $this->runMigration($migration, $dry);
            $executionTime = microtime(true) - $start;
            $output->writeln('<info>' . $this->migrationInfoPrefix . ' ' . $migration->getClassName() . ' executed</info>. <comment>Took ' . sprintf('%.4fs', $executionTime) . '</comment>', $optionsNormal);

            $executedQueries = $migration->getExecutedQueries();
            $output->writeln('Executed queries:', $optionsDebug);
            $output->writeln($executedQueries, $optionsDebug);

            $executedMigration = [
                'classname' => $migration->getClassName(),
                'execution_time' => $executionTime,
            ];
            if ($output->getVerbosity() === OutputInterface::VERBOSITY_DEBUG) {
                $executedMigration['executed_queries'] = $executedQueries;
            }
            $executedMigrations[] = $executedMigration;
        }

        if ($input->getOption('output-format') === 'json') {
            $output->write(json_encode(['executed_migrations' => $executedMigrations]));
        }
    }

    abstract protected function findMigrations(InputInterface $input);

    abstract protected function runMigration(AbstractMigration $migration, $dry = false);

    protected function finishCommand(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('output-format') === 'json') {
            return;
        }
        parent::finishCommand($input, $output);
    }
}
