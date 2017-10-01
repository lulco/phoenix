<?php

namespace Phoenix\Tests\Command\CreateCommand;

use Nette\Utils\Finder;
use Phoenix\Command\CreateCommand;
use Phoenix\Command\InitCommand;
use Phoenix\Exception\ConfigException;
use Phoenix\Exception\PhoenixException;
use Phoenix\Migration\ClassNameCreator;
use Phoenix\Tests\Command\BaseCommandTest;
use Phoenix\Tests\Mock\Command\Output;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

abstract class CreateCommandTest extends BaseCommandTest
{
    public function testDefaultName()
    {
        $command = new CreateCommand();
        $this->assertEquals('create', $command->getName());
    }

    public function testCustomName()
    {
        $command = new CreateCommand('my_create');
        $this->assertEquals('my_create', $command->getName());
    }

    public function testMissingDefaultConfig()
    {
        $command = new CreateCommand();
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('No configuration file exists. Create phoenix.php or phoenix.yml or phoenix.neon or phoenix.json in your project root or specify path to your existing config file with --config option');
        $command->run($this->input, $this->output);
    }

    public function testUserConfigFileNotFound()
    {
        $command = new CreateCommand();
        $this->input->setOption('config', 'xyz.neon');
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Configuration file "xyz.neon" doesn\'t exist.');
        $command->run($this->input, $this->output);
    }

    public function testNonExistingTemplateFileException()
    {
        $command = new CreateCommand();
        $command->setConfig($this->configuration);
        $this->input->setOption('template', 'non-existing-file.phoenix');

        $this->expectException(PhoenixException::class);
        $this->expectExceptionMessage('Template "non-existing-file.phoenix" doesn\'t exist or is not a regular file');
        $command->run($this->input, $this->output);
    }

    public function testMoreThanOneMigrationDirsAvailableWithCommandChoice()
    {
        $createMigrationDir = __DIR__ . '/../../../testing_migrations/new';
        $this->assertFalse(is_dir($createMigrationDir));
        mkdir($createMigrationDir);
        $this->assertTrue(is_dir($createMigrationDir));

        $configuration = $this->configuration;
        $configuration['migration_dirs']['create'] = $createMigrationDir;

        $command = new CreateCommand();
        $command->setConfig($configuration);

        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['create']);
        $commandTester->execute(['migration' => 'test\test']);

        $createFiles = Finder::find('*')->in($createMigrationDir);
        $this->assertCount(1, $createFiles);
        foreach ($createFiles as $createFile) {
            $filePath = (string)$createFile;

            $migrationContent = file_get_contents($filePath);
            $this->assertStringStartsWith('<?php', $migrationContent);
            $this->assertNotContains("\t", $migrationContent);
            $this->assertContains("    ", $migrationContent);

            $classNameCreator = new ClassNameCreator($filePath);
            $this->assertEquals('\Test\Test', $classNameCreator->getClassName());
            unlink($filePath);
        }
        rmdir($createMigrationDir);
    }

    public function testCreateMigrationInNewDirectory()
    {
        $createMigrationDir = __DIR__ . '/../../../testing_migrations/new';
        $this->assertFalse(is_dir($createMigrationDir));
        mkdir($createMigrationDir);
        $this->assertTrue(is_dir($createMigrationDir));

        $configuration = $this->configuration;
        $configuration['migration_dirs']['create'] = $createMigrationDir;

        $initCommand = new InitCommand();
        $input = $this->createInput();
        $initCommand->setConfig($configuration);
        $initCommand->run($input, new Output());

        $createFiles = Finder::find('*')->in($createMigrationDir);
        $this->assertCount(0, $createFiles);

        $command = new CreateCommand();
        $command->setConfig($configuration);
        $this->input->setArgument('migration', '\MyNamespace\MyFirstMigration');
        $this->input->setArgument('dir', 'create');
        $command->run($this->input, $this->output);

        $createFiles = Finder::find('*')->in($createMigrationDir);
        $this->assertCount(1, $createFiles);
        foreach ($createFiles as $createFile) {
            $filePath = (string)$createFile;

            $migrationContent = file_get_contents($filePath);
            $this->assertStringStartsWith('<?php', $migrationContent);
            $this->assertNotContains("\t", $migrationContent);
            $this->assertContains("    ", $migrationContent);

            $classNameCreator = new ClassNameCreator($filePath);
            $this->assertEquals('\MyNamespace\MyFirstMigration', $classNameCreator->getClassName());
            unlink($filePath);
        }

        $messages = $this->output->getMessages();

        $this->assertTrue(is_array($messages));
        $this->assertArrayHasKey(0, $messages);

        $this->assertCount(5, $messages[0]);
        $this->assertStringStartsWith('<info>Migration "\MyNamespace\MyFirstMigration" created in "' . realpath($createMigrationDir), $messages[0][1]);
        $this->assertArrayNotHasKey(OutputInterface::VERBOSITY_DEBUG, $messages);

        rmdir($createMigrationDir);
    }

    public function testCreateMigrationInNewDirectoryWithTabIndent()
    {
        $createMigrationDir = __DIR__ . '/../../../testing_migrations/new';
        $this->assertFalse(is_dir($createMigrationDir));
        mkdir($createMigrationDir);
        $this->assertTrue(is_dir($createMigrationDir));

        $configuration = $this->configuration;
        $configuration['migration_dirs']['create'] = $createMigrationDir;

        $initCommand = new InitCommand();
        $input = $this->createInput();
        $initCommand->setConfig($configuration);
        $initCommand->run($input, new Output());

        $createFiles = Finder::find('*')->in($createMigrationDir);
        $this->assertCount(0, $createFiles);

        $command = new CreateCommand();
        $command->setConfig($configuration);
        $this->input->setArgument('migration', '\MyNamespace\MyFirstMigration');
        $this->input->setArgument('dir', 'create');
        $this->input->setOption('indent', 'tab');
        $command->run($this->input, $this->output);

        $createFiles = Finder::find('*')->in($createMigrationDir);
        $this->assertCount(1, $createFiles);
        foreach ($createFiles as $createFile) {
            $filePath = (string)$createFile;

            $migrationContent = file_get_contents($filePath);
            $this->assertStringStartsWith('<?php', $migrationContent);
            $this->assertContains("\t", $migrationContent);
            $this->assertNotContains("    ", $migrationContent);

            $classNameCreator = new ClassNameCreator($filePath);
            $this->assertEquals('\MyNamespace\MyFirstMigration', $classNameCreator->getClassName());
            unlink($filePath);
        }

        $messages = $this->output->getMessages();

        $this->assertTrue(is_array($messages));
        $this->assertArrayHasKey(0, $messages);

        $this->assertCount(5, $messages[0]);
        $this->assertStringStartsWith('<info>Migration "\MyNamespace\MyFirstMigration" created in "' . realpath($createMigrationDir), $messages[0][1]);
        $this->assertArrayNotHasKey(OutputInterface::VERBOSITY_DEBUG, $messages);

        rmdir($createMigrationDir);
    }
}
