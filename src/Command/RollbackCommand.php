<?php

namespace Phoenix\Command;

use Phoenix\Migration\Manager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RollbackCommand extends AbstractCommand
{
    protected function configure()
    {
        $this->setName('rollback')
            ->addOption('all', null, InputOption::VALUE_NONE, 'Rollback all migrations')
            ->setDescription('Rollback all available migrations');
        
        parent::configure();
    }

    protected function runCommand(InputInterface $input, OutputInterface $output)
    {
        $target = $input->getOption('all') ? Manager::TARGET_ALL : Manager::TARGET_FIRST;
        $migrations = $this->manager->findMigrationsToExecute(Manager::TYPE_DOWN, $target);
        if (empty($migrations)) {
            $output->writeln('');
            $output->writeln('<info>Nothing to rollback</info>');
            return;
        }
        
        foreach ($migrations as $migration) {
            $output->writeln('');
            $output->writeln('<info>Rollback for migration ' . $migration->getClassName() . ' executing</info>');
            
            $start = microtime(true);
            $migration->rollback();
            $output->writeln('<info>Rollback for migration ' . $migration->getClassName() . ' executed</info>. <comment>Took ' . sprintf('%.4fs', microtime(true) - $start) . '</comment>');

            $this->manager->removeExecution($migration);
            
            $output->writeln('Executed queries:', OutputInterface::VERBOSITY_DEBUG);
            $output->writeln($migration->getExecutedQueries(), OutputInterface::VERBOSITY_DEBUG);
        }
    }
}
