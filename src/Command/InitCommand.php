<?php

declare(strict_types=1);

namespace Phoenix\Command;

use Phoenix\Migration\Init\Init;
use Symfony\Component\Console\Output\OutputInterface;

final class InitCommand extends AbstractCommand
{
    public function __construct(string $name = 'init')
    {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->setDescription('Initialize phoenix');
        parent::configure();
    }

    protected function runCommand(): void
    {
        $filename = __DIR__ . '/../Migration/Init/0_init.php';
        require_once $filename;
        $migration = new Init($this->adapter, $this->getConfig()->getLogTableName());
        $migration->migrate();

        $executedQueries = $migration->getExecutedQueries();
        $this->writeln(['', '<info>Phoenix initialized</info>']);
        $this->writeln(['Executed queries:'], OutputInterface::VERBOSITY_DEBUG);
        $this->writeln($executedQueries, OutputInterface::VERBOSITY_DEBUG);

        $this->outputData['message'] = 'Phoenix initialized';

        if ($this->output->getVerbosity() === OutputInterface::VERBOSITY_DEBUG) {
            $this->outputData['executed_queries'] = $executedQueries;
        }
    }
}
