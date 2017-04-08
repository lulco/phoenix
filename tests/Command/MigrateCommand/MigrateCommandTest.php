<?php

namespace Phoenix\Tests\Command\MigrateCommand;

use Phoenix\Command\CleanupCommand;
use Phoenix\Command\InitCommand;
use Phoenix\Command\MigrateCommand;
use Phoenix\Exception\ConfigException;
use Phoenix\Tests\Command\BaseCommandTest;
use Phoenix\Tests\Mock\Command\Output;
use Symfony\Component\Console\Output\OutputInterface;

abstract class MigrateCommandTest extends BaseCommandTest
{
    public function testDefaultName()
    {
        $command = new MigrateCommand();
        $this->assertEquals('migrate', $command->getName());
    }

    public function testCustomName()
    {
        $command = new MigrateCommand('my_migrate');
        $this->assertEquals('my_migrate', $command->getName());
    }

    public function testMissingDefaultConfig()
    {
        $command = new MigrateCommand();
        $this->setExpectedException(ConfigException::class, 'No configuration file exists. Create phoenix.php or phoenix.yml or phoenix.neon or phoenix.json in your project root or specify path to your existing config file with --config option');
        $command->run($this->input, $this->output);
    }

    public function testUserConfigFileNotFound()
    {
        $command = new MigrateCommand();
        $this->input->setOption('config', 'xyz.neon');
        $this->setExpectedException(ConfigException::class, 'Configuration file "xyz.neon" doesn\'t exist.');
        $command->run($this->input, $this->output);
    }

    public function testUserConfigFile()
    {
        $command = new MigrateCommand();
        $this->input->setOption('config', __DIR__ . '/../../../testing_migrations/config/phoenix.php');
        $command->run($this->input, $this->output);

        $messages = $this->output->getMessages();
        $this->assertTrue(is_array($messages));
        $this->assertArrayHasKey(0, $messages);
        $this->assertEquals('<info>Phoenix initialized</info>' . "\n", $messages[0][1]);
        $this->assertArrayHasKey(OutputInterface::VERBOSITY_DEBUG, $messages);
    }

    public function testSetCustomConfig()
    {
        $command = new MigrateCommand();
        $command->setConfig($this->configuration);
        $command->run($this->input, $this->output);

        $messages = $this->output->getMessages();
        $this->assertTrue(is_array($messages));
        $this->assertArrayHasKey(0, $messages);
        $this->assertEquals('<info>Phoenix initialized</info>' . "\n", $messages[0][1]);
        $this->assertArrayHasKey(OutputInterface::VERBOSITY_DEBUG, $messages);
    }

    public function testMultipleMigration()
    {
        $command = new MigrateCommand();
        $command->setConfig($this->configuration);
        $command->run($this->input, $this->output);

        $input = $this->createInput();
        $output = new Output();
        $command->run($input, $output);

        $messages = $output->getMessages();
        $this->assertTrue(is_array($messages));
        $this->assertArrayHasKey(0, $messages);
        $this->assertEquals('<info>Nothing to migrate</info>' . "\n", $messages[0][1]);
        $this->assertArrayNotHasKey(OutputInterface::VERBOSITY_DEBUG, $messages);
    }

    public function testOnlyFirstMigration()
    {
        $command = new MigrateCommand();
        $command->setConfig($this->configuration);

        $input = $this->createInput();
        $input->setOption('first', true);
        $output = new Output();
        $command->run($input, $output);
        $messagesFirst = $output->getMessages();

        $command = new CleanupCommand();
        $command->setConfig($this->configuration);
        $command->run($this->input, $this->output);

        $command = new MigrateCommand();
        $command->setConfig($this->configuration);
        $input = $this->createInput();
        $output = new Output();
        $command->run($input, $output);
        $messagesAll = $output->getMessages();

        $this->assertGreaterThan(count($messagesFirst[0]), count($messagesAll[0]));
    }

    public function testDryRun()
    {
        $initCommand = new InitCommand();
        $initCommand->setConfig($this->configuration);
        $initCommand->run($this->createInput(), new Output());

        $input = $this->createInput();
        $output = new Output();
        $command = new MigrateCommand();
        $command->setConfig($this->configuration);
        $input->setOption('dry', true);
        $input->setOption('first', true);
        $command->run($input, $output);

        $messages = $output->getMessages();
        $dryQueries = $messages[OutputInterface::VERBOSITY_DEBUG];

        $input = $this->createInput();
        $output = new Output();
        $input->setOption('first', true);
        $command->run($input, $output);

        $realRunMessages = $output->getMessages();
        $this->assertEquals($dryQueries, $realRunMessages[OutputInterface::VERBOSITY_DEBUG]);
    }
}
