<?php

namespace Phoenix\Command;

use Phoenix\Command\AbstractCommand;
use Phoenix\Migration\Init\Init;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class InitCommand extends AbstractCommand
{
    protected function configure()
    {
        $this->setName('init')
            ->setDescription('Initialize phoenix')
            ->addOption('output-format', 'f', InputOption::VALUE_REQUIRED, 'Format of the output. Available values: text, json', 'text');

        parent::configure();
    }

    protected function runCommand(InputInterface $input, OutputInterface $output)
    {
        $filename = __DIR__ . '/../Migration/Init/0_init.php';
        require_once $filename;
        $migration = new Init($this->adapter, $this->config->getLogTableName());
        $migration->migrate();

        $optionsNormal = $input->getOption('output-format') === 'json' ? -1 : 0;
        $optionsDebug = $input->getOption('output-format') === 'json' ? -1 : OutputInterface::VERBOSITY_DEBUG;

        $output->writeln('', $optionsNormal);
        $executedQueries = $migration->getExecutedQueries();
        $output->writeln('<info>Phoenix initialized</info>', $optionsNormal);
        $output->writeln('Executed queries:', $optionsDebug);
        $output->writeln($executedQueries, $optionsDebug);

        $outputData = [
            'message' => 'Phoenix initialized',
        ];
        if ($output->getVerbosity() === OutputInterface::VERBOSITY_DEBUG) {
            $outputData['executed_queries'] = $executedQueries;
        }

        if ($input->getOption('output-format') === 'json') {
            $output->write(json_encode($outputData));
        }
    }

    protected function finishCommand(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('output-format') === 'json') {
            return;
        }
        parent::finishCommand($input, $output);
    }
}
