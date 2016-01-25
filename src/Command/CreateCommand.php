<?php

namespace Phoenix\Command;

use Phoenix\Command\AbstractCommand;
use Phoenix\Migration\MigrationNameCreator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateCommand extends AbstractCommand
{
    protected function configure()
    {
        $this->setName('create')
            ->setDescription('Create migration')
            ->addArgument('migration', InputArgument::REQUIRED, 'Name of migration')
            ->addArgument('dir', InputArgument::OPTIONAL, 'Directory to create migration in');
        
        parent::configure();
    }

    protected function runCommand(InputInterface $input, OutputInterface $output)
    {
        $migration = $input->getArgument('migration');
        $migrationNameCreator = new MigrationNameCreator($migration);
        $filename = $migrationNameCreator->getFileName();
        $dir = $input->getArgument('dir');
        $migrationDir = $this->config->getMigrationDir($dir);
        
        $content = "<?php \n\n";
        if ($migrationNameCreator->getNamespace()) {
            $content .= "namespace {$migrationNameCreator->getNamespace()};\n\n"; 
        }
        $content .= "use Phoenix\Migration\AbstractMigration;\n\n";
        $content .= "class {$migrationNameCreator->getClassName()} extends AbstractMigration\n";
        $content .= "{\n    protected function up()\n    {\n        \n    }\n\n";
        $content .= "    protected function down()\n    {\n        \n    }\n}\n";
        file_put_contents($migrationDir . '/' . $filename, $content);
        
        $output->writeln('');
        $output->writeln('<info>Migration "' . $migration . '" created</info>');
        $output->writeln('');
    }
}
