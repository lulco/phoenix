<?php

namespace Phoenix\Command;

use Phoenix\Command\AbstractCommand;
use Phoenix\Migration\ClassNameCreator;
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
        $filename = ClassNameCreator::createMigrationName($migration);
        $dir = $input->getArgument('dir');
        $migrationDir = $this->config->getMigrationDir($dir);
        
        $classNameAndNamespace = ClassNameCreator::createClassNameAndNamespace($migration);
        $content = "<?php \n\n";
        if ($classNameAndNamespace['namespace']) {
            $content .= "namespace {$classNameAndNamespace['namespace']};\n\n"; 
        }
        $content .= "use Phoenix\Migration\AbstractMigration;\n\n";
        $content .= "class {$classNameAndNamespace['class_name']} extends AbstractMigration\n";
        $content .= "{\n    protected function up()\n    {\n        \n    }\n\n";
        $content .= "    protected function down()\n    {\n        \n    }\n}\n";
        file_put_contents($migrationDir . '/' . $filename, $content);
        
        $output->writeln('');
        $output->writeln('<info>Migration "' . $migration . '" created</info>');
        $output->writeln('');
    }
}
