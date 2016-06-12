<?php

namespace Phoenix\Command;

use Phoenix\Command\AbstractCommand;
use Phoenix\Migration\Init\Init;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InitCommand extends AbstractCommand
{
    protected function configure()
    {
        $this->setName('init')
            ->setDescription('Initialize phoenix');
        
        parent::configure();
    }

    protected function runCommand(InputInterface $input, OutputInterface $output)
    {
        $filename = __DIR__ . '/../Migration/Init/0_init.php';
        require_once $filename;
        $migration = new Init($this->adapter, $this->config->getLogTableName());
        $migration->migrate();
        
        $output->writeln('');
        $output->writeln('<info>Phoenix initialized</info>');
        $output->writeln('Executed queries:', OutputInterface::VERBOSITY_DEBUG);
        $output->writeln($migration->getExecutedQueries(), OutputInterface::VERBOSITY_DEBUG);
    }
}
