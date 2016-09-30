<?php

namespace Phoenix\Command;

use Phoenix\Command\AbstractCommand;
use Phoenix\Exception\PhoenixException;
use Phoenix\Migration\MigrationNameCreator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CreateCommand extends AbstractCommand
{
    protected function configure()
    {
		parent::configure();
        $this->setName('create')
            ->setDescription('Create migration')
            ->addArgument('migration', InputArgument::REQUIRED, 'Name of migration')
            ->addArgument('dir', InputArgument::OPTIONAL, 'Directory to create migration in')
            ->addOption('template', null, InputOption::VALUE_OPTIONAL, 'Path to template');
    }

    protected function runCommand(InputInterface $input, OutputInterface $output)
    {
        $migration = $input->getArgument('migration');
        $migrationNameCreator = new MigrationNameCreator($migration);
        $filename = $migrationNameCreator->getFileName();
        $dir = $input->getArgument('dir');
        $migrationDir = $this->config->getMigrationDir($dir);
        
        $templatePath = $input->getOption('template') ?: __DIR__ . '/../Templates/DefaultTemplate.phoenix';
        if (!file_exists($templatePath)) {
            throw new PhoenixException('Template "' . $templatePath . '" not found');
        }
        
        $template = file_get_contents($templatePath);
        $namespace = '';
        if ($migrationNameCreator->getNamespace()) {
            $namespace .= "namespace {$migrationNameCreator->getNamespace()};\n\n";
        }
        $template = str_replace('###NAMESPACE###', $namespace, $template);
        $template = str_replace('###CLASSNAME###', $migrationNameCreator->getClassName(), $template);
        
        file_put_contents($migrationDir . '/' . $filename, $template);

        $output->writeln('');
        $output->writeln('<info>Migration "' . $migration . '" created</info>');
    }
}
