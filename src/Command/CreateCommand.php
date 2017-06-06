<?php

namespace Phoenix\Command;

use Dumper\Indenter;
use Phoenix\Command\AbstractCommand;
use Phoenix\Exception\PhoenixException;
use Phoenix\Migration\MigrationNameCreator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class CreateCommand extends AbstractCommand
{
    protected function configure()
    {
        parent::configure();
        $this->setName('create')
            ->setDescription('Create migration')
            ->addArgument('migration', InputArgument::REQUIRED, 'Name of migration')
            ->addArgument('dir', InputArgument::OPTIONAL, 'Directory to create migration in')
            ->addOption('template', null, InputOption::VALUE_REQUIRED, 'Path to template')
            ->addOption('indent', 'i', InputOption::VALUE_REQUIRED, 'Indentation. Available values: 2spaces, 3spaces, 4spaces, 5spaces, tab', '4spaces');
    }

    protected function runCommand()
    {
        $migration = $this->input->getArgument('migration');
        $migrationNameCreator = new MigrationNameCreator($migration);
        $filename = $migrationNameCreator->getFileName();
        $dir = $this->input->getArgument('dir');
        $migrationDir = $this->config->getMigrationDir($dir);

        $templatePath = $this->input->getOption('template') ?: __DIR__ . '/../Templates/DefaultTemplate.phoenix';
        if (!is_file($templatePath)) {
            throw new PhoenixException('Template "' . $templatePath . '" doesn\'t exist or is not a regular file');
        }

        $indenter = new Indenter();
        $indent = $indenter->indent($this->input->getOption('indent'));

        $template = file_get_contents($templatePath);
        $namespace = '';
        if ($migrationNameCreator->getNamespace()) {
            $namespace .= "namespace {$migrationNameCreator->getNamespace()};\n\n";
        }
        $template = str_replace('###NAMESPACE###', $namespace, $template);
        $template = str_replace('###CLASSNAME###', $migrationNameCreator->getClassName(), $template);
        $template = str_replace('###INDENT###', $indent, $template);
        $template = str_replace(['###UP###', '###DOWN###'], str_repeat($indent, 2), $template);

        $migrationPath = $migrationDir . '/' . $filename;
        file_put_contents($migrationPath, $template);
        $migrationPath = realpath($migrationPath);

        $this->writeln('');
        $this->writeln('<info>Migration "' . $migration . '" created in "' . $migrationPath . '"</info>');

        $this->outputData['migration_name'] = $migration;
        $this->outputData['migration_filepath'] = $migrationPath;
    }
}
