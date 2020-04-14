<?php

namespace Phoenix\Tests\Command\DiffCommand;

use Phoenix\Command\DiffCommand;
use Phoenix\Command\InitCommand;
use Phoenix\Command\MigrateCommand;
use Phoenix\Exception\ConfigException;
use Phoenix\Exception\InvalidArgumentValueException;
use Phoenix\Exception\PhoenixException;
use Phoenix\Migration\ClassNameCreator;
use Phoenix\Tests\Command\BaseCommandTest;
use Phoenix\Tests\Mock\Command\Output;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Finder\Finder;

abstract class DiffCommandTest extends BaseCommandTest
{
    public function testDefaultName()
    {
        $command = new DiffCommand();
        $this->assertEquals('diff', $command->getName());
    }

    public function testCustomName()
    {
        $command = new DiffCommand('my_diff');
        $this->assertEquals('my_diff', $command->getName());
    }

    public function testMissingDefaultConfig()
    {
        $command = new DiffCommand();
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('No configuration file exists. Create phoenix.php or phoenix.yml or phoenix.neon or phoenix.json in your project root or specify path to your existing config file with --config option');
        $command->run($this->input, $this->output);
    }

    public function testUserConfigFileNotFound()
    {
        $command = new DiffCommand();
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

        $command = new DiffCommand();
        $command->setConfig($this->configuration);
        $this->input->setOption('template', 'non-existing-file.phoenix');
        $this->input->setOption('indent', '4spaces');

        $this->expectException(PhoenixException::class);
        $this->expectExceptionMessage('Template "non-existing-file.phoenix" not found');
        $command->run($this->input, $this->output);
    }

    public function testSourceNotFound()
    {
        $diffMigrationDir = __DIR__ . '/../../../testing_migrations/new';
        $this->assertFalse(is_dir($diffMigrationDir));
        mkdir($diffMigrationDir);
        $this->assertTrue(is_dir($diffMigrationDir));

        $configuration = $this->configuration;
        $configuration['migration_dirs']['diff'] = $diffMigrationDir;

        $initCommand = new InitCommand();
        $input = $this->createInput();
        $initCommand->setConfig($configuration);
        $initCommand->run($input, new Output());

        $command = new DiffCommand();
        $command->setConfig($configuration);

        $this->input->setOption('indent', '4spaces');
        $this->input->setOption('source', 'source');
        $this->input->setOption('target', $this->getEnvironment());
        $this->input->setOption('dir', 'diff');

        $this->expectException(InvalidArgumentValueException::class);
        $this->expectExceptionMessage('Source environment "source" doesn\'t exist in config');
        $command->run($this->input, $this->output);
    }

    public function testTargetNotFound()
    {
        $diffMigrationDir = __DIR__ . '/../../../testing_migrations/new';
        $this->assertFalse(is_dir($diffMigrationDir));
        mkdir($diffMigrationDir);
        $this->assertTrue(is_dir($diffMigrationDir));

        $configuration = $this->configuration;
        $configuration['migration_dirs']['diff'] = $diffMigrationDir;

        $initCommand = new InitCommand();
        $input = $this->createInput();
        $initCommand->setConfig($configuration);
        $initCommand->run($input, new Output());

        $command = new DiffCommand();
        $command->setConfig($configuration);
        $this->input->setOption('indent', '4spaces');
        $this->input->setOption('source', $this->getEnvironment());
        $this->input->setOption('target', 'lalala');
        $this->input->setOption('dir', 'diff');

        $this->expectException(InvalidArgumentValueException::class);
        $this->expectExceptionMessage('Target environment "lalala" doesn\'t exist in config');
        $command->run($this->input, $this->output);
    }

    public function testMoreThanOneMigrationDirsAvailableWithCommandChoice()
    {
        $diffMigrationDir = __DIR__ . '/../../../testing_migrations/new';
        $this->assertFalse(is_dir($diffMigrationDir));
        mkdir($diffMigrationDir);
        $this->assertTrue(is_dir($diffMigrationDir));

        $configuration = $this->configuration;
        $configuration['migration_dirs']['diff'] = $diffMigrationDir;

        $initCommand = new InitCommand();
        $input = $this->createInput();
        $initCommand->setConfig($configuration);
        $initCommand->run($input, new Output());

        $command = new DiffCommand();
        $command->setConfig($configuration);
        $this->input->setOption('indent', '4spaces');

        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['diff']);
        $commandTester->execute([]);

        $diffFiles = Finder::create()->files()->in($diffMigrationDir);
        $this->assertCount(1, $diffFiles);
        foreach ($diffFiles as $diffFile) {
            $filePath = (string)$diffFile;

            $migrationContent = file_get_contents($filePath);
            $this->assertStringStartsWith('<?php', $migrationContent);
            $this->assertStringNotContainsString("\t", $migrationContent);
            $this->assertStringContainsString("    ", $migrationContent);

            $classNameCreator = new ClassNameCreator($filePath);
            $this->assertEquals('\Diff', $classNameCreator->getClassName());
            unlink($filePath);
        }
        rmdir($diffMigrationDir);
    }

    public function testDiffCommandAfterInit()
    {
        $diffMigrationDir = __DIR__ . '/../../../testing_migrations/new';
        $this->assertFalse(is_dir($diffMigrationDir));
        mkdir($diffMigrationDir);
        $this->assertTrue(is_dir($diffMigrationDir));

        $configuration = $this->configuration;
        $configuration['migration_dirs']['diff'] = $diffMigrationDir;

        $diffFiles = Finder::create()->files()->in($diffMigrationDir);
        $this->assertCount(0, $diffFiles);

        $initCommand = new InitCommand();
        $input = $this->createInput();
        $initCommand->setConfig($configuration);
        $initCommand->run($input, new Output());

        $command = new DiffCommand();
        $command->setConfig($configuration);
        $this->input->setOption('indent', '4spaces');
        $this->input->setOption('dir', 'diff');
        $command->run($this->input, $this->output);

        $diffFiles = Finder::create()->files()->in($diffMigrationDir);
        $this->assertCount(1, $diffFiles);
        foreach ($diffFiles as $diffFile) {
            $filePath = (string)$diffFile;

            $migrationContent = file_get_contents($filePath);
            $this->assertStringStartsWith('<?php', $migrationContent);
            $this->assertStringNotContainsString("\t", $migrationContent);
            $this->assertStringContainsString("    ", $migrationContent);

            $classNameCreator = new ClassNameCreator($filePath);
            $this->assertEquals('\Diff', $classNameCreator->getClassName());
            unlink($filePath);
        }

        $messages = $this->output->getMessages();

        $this->assertTrue(is_array($messages));
        $this->assertArrayHasKey(0, $messages);
        $this->assertCount(5, $messages[0]);
        $this->assertStringStartsWith('<info>Migration "Diff" created in "' . realpath($diffMigrationDir), $messages[0][1]);
        $this->assertArrayNotHasKey(OutputInterface::VERBOSITY_DEBUG, $messages);

        rmdir($diffMigrationDir);
    }

    public function testDiffCommandAfterMigration()
    {
        $diffMigrationDir = __DIR__ . '/../../../testing_migrations/new';
        $this->assertFalse(is_dir($diffMigrationDir));
        mkdir($diffMigrationDir);
        $this->assertTrue(is_dir($diffMigrationDir));

        $configuration = $this->configuration;
        $configuration['migration_dirs']['diff'] = $diffMigrationDir;

        $diffFiles = Finder::create()->files()->in($diffMigrationDir);
        $this->assertCount(0, $diffFiles);

        $migrateCommand = new MigrateCommand();
        $input = $this->createInput();
        $migrateCommand->setConfig($configuration);
        $migrateCommand->run($input, new Output());

        $command = new DiffCommand();
        $command->setConfig($configuration);
        $this->input->setOption('ignore-tables', 'all_types');
        $this->input->setOption('indent', 'tab');
        $this->input->setOption('migration', '\MyNamespace\MyMigration');
        $this->input->setOption('dir', 'diff');

        $command->run($this->input, $this->output);

        $diffFiles = Finder::create()->files()->in($diffMigrationDir);
        $this->assertCount(1, $diffFiles);
        foreach ($diffFiles as $diffFile) {
            $filePath = (string)$diffFile;
            $migrationContent = file_get_contents($filePath);
            $this->assertStringStartsWith('<?php', $migrationContent);
            $this->assertStringContainsString("\t", $migrationContent);
            $this->assertStringNotContainsString("    ", $migrationContent);
            $classNameCreator = new ClassNameCreator($filePath);
            $this->assertEquals('\MyNamespace\MyMigration', $classNameCreator->getClassName());
            unlink($filePath);
        }
        rmdir($diffMigrationDir);

        $messages = $this->output->getMessages();

        $this->assertTrue(is_array($messages));
        $this->assertArrayHasKey(0, $messages);
        $this->assertCount(5, $messages[0]);
        $this->assertStringStartsWith('<info>Migration "\MyNamespace\MyMigration" created in "' . realpath($diffMigrationDir), $messages[0][1]);
        $this->assertArrayNotHasKey(OutputInterface::VERBOSITY_DEBUG, $messages);
    }

    public function testDiffCommandJsonOutputAfterMigration()
    {
        $diffMigrationDir = __DIR__ . '/../../../testing_migrations/new';
        $this->assertFalse(is_dir($diffMigrationDir));
        mkdir($diffMigrationDir);
        $this->assertTrue(is_dir($diffMigrationDir));

        $configuration = $this->configuration;
        $configuration['migration_dirs']['diff'] = $diffMigrationDir;

        $diffFiles = Finder::create()->files()->in($diffMigrationDir);
        $this->assertCount(0, $diffFiles);

        $migrateCommand = new MigrateCommand();
        $input = $this->createInput();
        $migrateCommand->setConfig($configuration);
        $migrateCommand->run($input, new Output());

        $command = new DiffCommand();
        $command->setConfig($configuration);
        $this->input->setOption('ignore-tables', 'all_types');
        $this->input->setOption('indent', 'tab');
        $this->input->setOption('migration', '\MyNamespace\MyMigration');
        $this->input->setOption('dir', 'diff');
        $this->input->setOption('output-format', 'json');

        $command->run($this->input, $this->output);

        $diffFiles = Finder::create()->files()->in($diffMigrationDir);
        $this->assertCount(1, $diffFiles);
        foreach ($diffFiles as $diffFile) {
            $filePath = (string)$diffFile;
            $migrationContent = file_get_contents($filePath);
            $this->assertStringStartsWith('<?php', $migrationContent);
            $this->assertStringContainsString("\t", $migrationContent);
            $this->assertStringNotContainsString("    ", $migrationContent);
            $classNameCreator = new ClassNameCreator($filePath);
            $this->assertEquals('\MyNamespace\MyMigration', $classNameCreator->getClassName());
            unlink($filePath);
        }
        rmdir($diffMigrationDir);

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

    protected function createInput()
    {
        $input = parent::createInput();
        $input->setOption('source', $this->getEnvironment());
        $input->setOption('target', $this->getEnvironment());
        return $input;
    }
}
