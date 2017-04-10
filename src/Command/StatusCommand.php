<?php

namespace Phoenix\Command;

use Phoenix\Command\AbstractCommand;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StatusCommand extends AbstractCommand
{
    protected function configure()
    {
        $this->setName('status')
            ->setDescription('List of migrations already executed and list of migrations to execute');
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
        $this->outputData['executed_migrations'] = $executedMigrations;

        $migrationsToExecute = [];
        foreach ($this->manager->findMigrationsToExecute() as $migration) {
            $migrationsToExecute[] = [
                'migration_datetime' => $migration->getDatetime(),
                'classname' => $migration->getClassName()
            ];
        }
        $this->outputData['migrations_to_execute'] = $migrationsToExecute;

        if ($this->isDefaultOutput()) {
            $this->printTable(['Migration datetime', 'Class name', 'Executed at'], $executedMigrations, 'Executed migrations', 'No executed migrations');
            $this->printTable(['Migration datetime', 'Class name'], $migrationsToExecute, 'Migrations to execute', 'No migrations to execute');
        }
    }

    private function printTable(array $headers, array $rows, $header, $noItemsText)
    {
        $this->writeln('');
        $this->writeln("<comment>$header</comment>");
        if (empty($rows)) {
            $this->writeln("<info>$noItemsText</info>");
            return;
        }
        $table = new Table($this->output);
        $table->setHeaders($headers);
        $table->setRows($rows);
        $table->render();
    }
}
