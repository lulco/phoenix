<?php

namespace Phoenix\Command;

use Phoenix\Command\AbstractCommand;
use Phoenix\Migration\Init\Init;
use Symfony\Component\Console\Output\OutputInterface;

class InitCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this->setName('init')
            ->setDescription('Initialize phoenix');
        parent::configure();
    }

    protected function runCommand(): void
    {
        $filename = __DIR__ . '/../Migration/Init/0_init.php';
        require_once $filename;
        $migration = new Init($this->adapter, $this->config->getLogTableName());
        $migration->migrate();

        $this->writeln('');
        $executedQueries = $migration->getExecutedQueries();
        $this->writeln('<info>Phoenix initialized</info>');
        $this->writeln('Executed queries:', OutputInterface::VERBOSITY_DEBUG);
        $this->writeln($executedQueries, OutputInterface::VERBOSITY_DEBUG);

        $this->outputData['message'] = 'Phoenix initialized';

        if ($this->output->getVerbosity() === OutputInterface::VERBOSITY_DEBUG) {
            $this->outputData['executed_queries'] = $executedQueries;
        }
    }
}
