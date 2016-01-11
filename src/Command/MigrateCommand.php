<?php

namespace Phoenix\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateCommand extends AbstractCommand
{
    protected function configure()
    {
        $this->setName('migrate')
            ->setDescription('Run all available migrations');
        
        parent::configure();
    }

    protected function runCommand(InputInterface $input, OutputInterface $output)
    {
        $migrations = $this->manager->findMigrationsToExecute();
        if (empty($migrations)) {
            $output->writeln('');
            $output->writeln('<info>Nothing to migrate</info>');
            $output->writeln('');
            return;
        }
        
        foreach ($migrations as $migration) {
            $migration->migrate();
            $this->manager->logExecution($migration);
            
            $output->writeln('');
            $output->writeln('<info>Migration ' . $migration->getClassName() . ' executed</info>');
            $output->writeln('Executed queries:', OutputInterface::VERBOSITY_DEBUG);
            $output->writeln($migration->getExecutedQueries(), OutputInterface::VERBOSITY_DEBUG);
        }
        $output->writeln('');
    }
}
