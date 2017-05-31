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
            ->addOption('template', null, InputOption::VALUE_REQUIRED, 'Path to template')
            ->addOption('indent', 'i', InputOption::VALUE_REQUIRED, 'Indentation. Available values: 2spaces, 3spaces, 4spaces, 5spaces, tab', '4spaces');
    }

    protected function runCommand(InputInterface $input, OutputInterface $output)
    {
        $migration = $input->getArgument('migration');
        $migrationNameCreator = new MigrationNameCreator($migration);
        $filename = $migrationNameCreator->getFileName();
        $dir = $input->getArgument('dir');
        $migrationDir = $this->config->getMigrationDir($dir);

        $templatePath = $input->getOption('template') ?: __DIR__ . '/../Templates/DefaultTemplate.phoenix';
        if (!is_file($templatePath)) {
            throw new PhoenixException('Template "' . $templatePath . '" doesn\'t exist or is not a regular file');
        }

        $indent = $this->getIndent($input);

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

        $this->writeln('');
        $this->writeln('<info>Migration "' . $migration . '" created in "' . $migrationPath . '"</info>');

        $this->outputData['migration_name'] = $migration;
        $this->outputData['migration_filepath'] = $migrationPath;
    }

    private function getIndent(InputInterface $input)
    {
        $indent = strtolower(str_replace([' ', '-', '_'], '', $input->getOption('indent')));
        if ($indent == '2spaces') {
            return '  ';
        }
        if ($indent == '3spaces') {
            return '   ';
        }
        if ($indent == '5spaces') {
            return '     ';
        }
        if ($indent == 'tab') {
            return "\t";
        }
        return '    ';
    }
}
