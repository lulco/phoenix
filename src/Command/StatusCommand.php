<?php

namespace Phoenix\Command;

use Phoenix\Command\AbstractCommand;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class StatusCommand extends AbstractCommand
{
    protected function configure()
    {
        $this->setName('status')
            ->setDescription('List of migrations already executed and list of migrations to execute')
            ->addOption('output-format', 'f', InputOption::VALUE_REQUIRED, 'Format of the output. Available values: table, json', 'table');
        parent::configure();
    }

    protected function runCommand(InputInterface $input, OutputInterface $output)
    {
        $executedMigrations = [];
        foreach ($this->manager->executedMigrations() as $migration) {
            $executedMigrations[] = [
                'migration_datetime' => $migration['migration_datetime'],
                'classname' => ltrim($migration['classname'], '\\'),
                'executed_at' => $migration['executed_at'],
            ];
        }

        $migrationsToExecute = [];
        foreach ($this->manager->findMigrationsToExecute() as $migration) {
            $migrationsToExecute[] = [
                'migration_datetime' => $migration->getDatetime(),
                'classname' => $migration->getClassName()
            ];
        }

        if ($input->getOption('output-format') === 'json') {
            $output->write(json_encode(['executed_migrations' => $executedMigrations, 'migrations_to_execute' => $migrationsToExecute]));
            return;
        }

        $this->printTable(['Migration datetime', 'Class name', 'Executed at'], $executedMigrations, $output, 'Executed migrations', 'No executed migrations');
        $this->printTable(['Migration datetime', 'Class name'], $migrationsToExecute, $output, 'Migrations to execute', 'No migrations to execute');
    }

    protected function finishCommand(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('output-format') === 'json') {
            return;
        }
        parent::finishCommand($input, $output);
    }

    private function printTable(array $headers, array $rows, OutputInterface $output, $header, $noItemsText)
    {
        $output->writeln('');
        $output->writeln("<comment>$header</comment>");
        if (empty($rows)) {
            $output->writeln("<info>$noItemsText</info>");
            return;
        }
        $table = new Table($output);
        $table->setHeaders($headers);
        $table->setRows($rows);
        $table->render();
    }
}
