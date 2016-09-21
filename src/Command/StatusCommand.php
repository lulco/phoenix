<?php

namespace Phoenix\Command;

use Phoenix\Command\AbstractCommand;
use Phoenix\Migration\Manager;
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
        $output->writeln('');
        $executedMigrations = $this->manager->executedMigrations();

        $output->writeln('<comment>Executed migrations</comment>');
        if (!$executedMigrations) {
            $output->writeln('<info>No executed migrations</info>');
        } else {
            $table = new Table($output);
            $table->setHeaders(['Class name']);
            $rows = [];
            foreach ($executedMigrations as $migration) {
                $rows[] = [ltrim($migration['classname'], '\\')];
            }
            $table->setRows($rows);
            $table->render();
        }

        $output->writeln('');

        $migrations = $this->manager->findMigrationsToExecute(Manager::TYPE_UP);
        $output->writeln('<comment>Migrations to execute</comment>');
        if (!$migrations) {
            $output->writeln('<info>No migrations to execute</info>');
        } else {
            $table = new Table($output);
            $table->setHeaders(['Class name']);
            $rows = [];
            foreach ($migrations as $migration) {
                $rows[] = [$migration->getClassName()];
            }
            $table->setRows($rows);
            $table->render();
        }
    }
}
