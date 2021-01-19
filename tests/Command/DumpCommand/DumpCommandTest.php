<?php

namespace Phoenix\Tests\Command\DumpCommand;

use Phoenix\Command\DumpCommand;
use Phoenix\Command\InitCommand;
use Phoenix\Command\MigrateCommand;
use Phoenix\Exception\ConfigException;
use Phoenix\Exception\PhoenixException;
use Phoenix\Migration\ClassNameCreator;
use Phoenix\Tests\Command\BaseCommandTest;
use Phoenix\Tests\Mock\Command\Output;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Finder\Finder;

abstract class DumpCommandTest extends BaseCommandTest
{
    public function testDefaultName()
    {
        $command = new DumpCommand();
        $this->assertEquals('dump', $command->getName());
    }

    public function testCustomName()
    {
        $command = new DumpCommand('my_dump');
        $this->assertEquals('my_dump', $command->getName());
    }

    public function testMissingDefaultConfig()
    {
        $command = new DumpCommand();
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('No configuration file exists. Create phoenix.php or phoenix.yml or phoenix.neon or phoenix.json in your project root or specify path to your existing config file with --config option');
        $command->run($this->input, $this->output);
    }

    public function testUserConfigFileNotFound()
    {
        $command = new DumpCommand();
        $this->input->setOption('config', 'xyz.neon');
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Configuration file "xyz.neon" doesn\'t exist.');
        $command->run($this->input, $this->output);
    }

    public function testNonExistingTemplateFileException()
    {
        $initCommand = new InitCommand();
        $input = $this->createInput();
        $initCommand->setConfig($this->configuration);
        $initCommand->run($input, new Output());

        $command = new DumpCommand();
        $command->setConfig($this->configuration);
        $this->input->setOption('template', 'non-existing-file.phoenix');
        $this->input->setOption('indent', '4spaces');

        $this->expectException(PhoenixException::class);
        $this->expectExceptionMessage('Template "non-existing-file.phoenix" not found');
        $command->run($this->input, $this->output);
    }

    public function testMoreThanOneMigrationDirsAvailableWithCommandChoice()
    {
        $dumpMigrationDir = __DIR__ . '/../../../testing_migrations/new';
        $this->assertFalse(is_dir($dumpMigrationDir));
        mkdir($dumpMigrationDir);
        $this->assertTrue(is_dir($dumpMigrationDir));

        $configuration = $this->configuration;
        $configuration['migration_dirs']['dump'] = $dumpMigrationDir;

        $initCommand = new InitCommand();
        $input = $this->createInput();
        $initCommand->setConfig($configuration);
        $initCommand->run($input, new Output());

        $command = new DumpCommand();
        $command->setConfig($configuration);
        $this->input->setOption('indent', '4spaces');

        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['dump']);
        $commandTester->execute([]);

        $dumpFiles = Finder::create()->files()->in($dumpMigrationDir);
        $this->assertCount(1, $dumpFiles);
        foreach ($dumpFiles as $dumpFile) {
            $filePath = (string)$dumpFile;

            $migrationContent = file_get_contents($filePath);
            $this->assertStringStartsWith('<?php', $migrationContent);
            $this->assertStringNotContainsString("\t", $migrationContent);
            $this->assertStringContainsString("    ", $migrationContent);

            $classNameCreator = new ClassNameCreator($filePath);
            $this->assertEquals('\Initialization', $classNameCreator->getClassName());
            unlink($filePath);
        }
        rmdir($dumpMigrationDir);
    }

    public function testDumpCommandAfterInit()
    {
        $dumpMigrationDir = __DIR__ . '/../../../testing_migrations/new';
        $this->assertFalse(is_dir($dumpMigrationDir));
        mkdir($dumpMigrationDir);
        $this->assertTrue(is_dir($dumpMigrationDir));

        $configuration = $this->configuration;
        $configuration['migration_dirs']['dump'] = $dumpMigrationDir;

        $dumpFiles = Finder::create()->files()->in($dumpMigrationDir);
        $this->assertCount(0, $dumpFiles);

        $initCommand = new InitCommand();
        $input = $this->createInput();
        $initCommand->setConfig($configuration);
        $initCommand->run($input, new Output());

        $command = new DumpCommand();
        $command->setConfig($configuration);
        $this->input->setOption('indent', '4spaces');
        $this->input->setOption('dir', 'dump');
        $command->run($this->input, $this->output);

        $dumpFiles = Finder::create()->files()->in($dumpMigrationDir);
        $this->assertCount(1, $dumpFiles);
        foreach ($dumpFiles as $dumpFile) {
            $filePath = (string)$dumpFile;

            $migrationContent = file_get_contents($filePath);
            $this->assertStringStartsWith('<?php', $migrationContent);
            $this->assertStringNotContainsString("\t", $migrationContent);
            $this->assertStringContainsString("    ", $migrationContent);

            $classNameCreator = new ClassNameCreator($filePath);
            $this->assertEquals('\Initialization', $classNameCreator->getClassName());
            unlink($filePath);
        }

        $messages = $this->output->getMessages();

        $this->assertTrue(is_array($messages));
        $this->assertArrayHasKey(0, $messages);
        $this->assertCount(5, $messages[0]);
        $this->assertStringStartsWith('<info>Migration "Initialization" created in "' . realpath($dumpMigrationDir), $messages[0][1]);
        $this->assertArrayNotHasKey(OutputInterface::VERBOSITY_DEBUG, $messages);

        rmdir($dumpMigrationDir);
    }

    public function testDumpCommandAfterMigration()
    {
        $dumpMigrationDir = __DIR__ . '/../../../testing_migrations/new';
        $this->assertFalse(is_dir($dumpMigrationDir));
        mkdir($dumpMigrationDir);
        $this->assertTrue(is_dir($dumpMigrationDir));

        $configuration = $this->configuration;
        $configuration['migration_dirs']['dump'] = $dumpMigrationDir;

        $dumpFiles = Finder::create()->files()->in($dumpMigrationDir);
        $this->assertCount(0, $dumpFiles);

        $migrateCommand = new MigrateCommand();
        $input = $this->createInput();
        $migrateCommand->setConfig($configuration);
        $migrateCommand->run($input, new Output());

        $command = new DumpCommand();
        $command->setConfig($configuration);
        $this->input->setOption('data', true);
        $this->input->setOption('ignore-tables', 'all_types');
        $this->input->setOption('ignore-data-tables', 'table_3');
        $this->input->setOption('indent', 'tab');
        $this->input->setOption('migration', '\MyNamespace\MyMigration');
        $this->input->setOption('dir', 'dump');

        $command->run($this->input, $this->output);

        $dumpFiles = Finder::create()->files()->in($dumpMigrationDir);
        $this->assertCount(1, $dumpFiles);
        foreach ($dumpFiles as $dumpFile) {
            $filePath = (string)$dumpFile;
            $migrationContent = file_get_contents($filePath);
            $this->assertStringStartsWith('<?php', $migrationContent);
            $this->assertStringContainsString("\t", $migrationContent);
            $this->assertStringNotContainsString("    ", $migrationContent);
            $classNameCreator = new ClassNameCreator($filePath);
            $this->assertEquals('\MyNamespace\MyMigration', $classNameCreator->getClassName());
            unlink($filePath);
        }
        rmdir($dumpMigrationDir);

        $messages = $this->output->getMessages();

        $this->assertTrue(is_array($messages));
        $this->assertArrayHasKey(0, $messages);
        $this->assertCount(5, $messages[0]);
        $this->assertStringStartsWith('<info>Migration "\MyNamespace\MyMigration" created in "' . realpath($dumpMigrationDir), $messages[0][1]);
        $this->assertArrayNotHasKey(OutputInterface::VERBOSITY_DEBUG, $messages);
    }

    public function testDumpCommandJsonOutputAfterMigration()
    {
        $dumpMigrationDir = __DIR__ . '/../../../testing_migrations/new';
        $this->assertFalse(is_dir($dumpMigrationDir));
        mkdir($dumpMigrationDir);
        $this->assertTrue(is_dir($dumpMigrationDir));

        $configuration = $this->configuration;
        $configuration['migration_dirs']['dump'] = $dumpMigrationDir;

        $dumpFiles = Finder::create()->files()->in($dumpMigrationDir);
        $this->assertCount(0, $dumpFiles);

        $migrateCommand = new MigrateCommand();
        $input = $this->createInput();
        $migrateCommand->setConfig($configuration);
        $migrateCommand->run($input, new Output());

        $command = new DumpCommand();
        $command->setConfig($configuration);
        $this->input->setOption('data', true);
        $this->input->setOption('ignore-tables', 'all_types');
        $this->input->setOption('ignore-data-tables', 'table_3');
        $this->input->setOption('indent', 'tab');
        $this->input->setOption('migration', '\MyNamespace\MyMigration');
        $this->input->setOption('dir', 'dump');
        $this->input->setOption('output-format', 'json');

        $command->run($this->input, $this->output);

        $dumpFiles = Finder::create()->files()->in($dumpMigrationDir);
        $this->assertCount(1, $dumpFiles);
        foreach ($dumpFiles as $dumpFile) {
            $filePath = (string)$dumpFile;
            $migrationContent = file_get_contents($filePath);
            $this->assertStringStartsWith('<?php', $migrationContent);
            $this->assertStringContainsString("\t", $migrationContent);
            $this->assertStringNotContainsString("    ", $migrationContent);
            $classNameCreator = new ClassNameCreator($filePath);
            $this->assertEquals('\MyNamespace\MyMigration', $classNameCreator->getClassName());
            unlink($filePath);
        }
        rmdir($dumpMigrationDir);

        $messages = $this->output->getMessages(0);

        $this->assertTrue(is_array($messages));
        $this->assertCount(1, $messages);
        $this->assertArrayHasKey(0, $messages);
        $this->assertJson($messages[0]);

        $message = json_decode($messages[0], true);

        $this->assertArrayHasKey('migration_name', $message);
        $this->assertEquals('\MyNamespace\MyMigration', $message['migration_name']);
        $this->assertArrayHasKey('migration_filepath', $message);
        $this->assertArrayHasKey('execution_time', $message);
    }
}
