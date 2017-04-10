<?php

namespace Phoenix\Tests\Command\RollbackCommand;

use Phoenix\Command\InitCommand;
use Phoenix\Command\MigrateCommand;
use Phoenix\Command\RollbackCommand;
use Phoenix\Exception\ConfigException;
use Phoenix\Tests\Command\BaseCommandTest;
use Phoenix\Tests\Mock\Command\Output;
use Symfony\Component\Console\Output\OutputInterface;

abstract class RollbackCommandTest extends BaseCommandTest
{
    public function testDefaultName()
    {
        $command = new RollbackCommand();
        $this->assertEquals('rollback', $command->getName());
    }

    public function testCustomName()
    {
        $command = new RollbackCommand('my_rollback');
        $this->assertEquals('my_rollback', $command->getName());
    }

    public function testMissingDefaultConfig()
    {
        $command = new RollbackCommand();
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('No configuration file exists. Create phoenix.php or phoenix.yml or phoenix.neon or phoenix.json in your project root or specify path to your existing config file with --config option');
        $command->run($this->input, $this->output);
    }

    public function testUserConfigFileNotFound()
    {
        $command = new RollbackCommand();
        $this->input->setOption('config', 'xyz.neon');
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Configuration file "xyz.neon" doesn\'t exist.');
        $command->run($this->input, $this->output);
    }

    public function testUserConfigFile()
    {
        $command = new RollbackCommand();
        $this->input->setOption('config', __DIR__ . '/../../../testing_migrations/config/phoenix.php');
        $command->run($this->input, $this->output);
    }

    public function testNothingToRollbackWithoutInitializing()
    {
        $command = new RollbackCommand();
        $this->input->setOption('config', __DIR__ . '/../../../testing_migrations/config/phoenix.php');
        $command->run($this->input, $this->output);

        $messages = $this->output->getMessages();
        $this->assertTrue(is_array($messages));
        $this->assertArrayHasKey(0, $messages);
        $this->assertEquals('<info>Phoenix initialized</info>' . "\n", $messages[0][1]);
        $this->assertEquals('<info>Nothing to rollback</info>' . "\n", $messages[0][6]);
        $this->assertArrayHasKey(OutputInterface::VERBOSITY_DEBUG, $messages);
    }

    public function testNothingToRollbackWithInitializing()
    {
        $input = $this->createInput();
        $output = new Output();
        $command = new InitCommand();
        $command->setConfig($this->configuration);
        $command->run($input, $output);

        $command = new RollbackCommand();
        $command->setConfig($this->configuration);
        $command->run($this->input, $this->output);

        $messages = $this->output->getMessages();
        $this->assertTrue(is_array($messages));
        $this->assertArrayHasKey(0, $messages);
        $this->assertEquals('<info>Nothing to rollback</info>' . "\n", $messages[0][1]);
        $this->assertArrayNotHasKey(OutputInterface::VERBOSITY_DEBUG, $messages);
    }

    public function testMigrateAndRollback()
    {
        $command = new MigrateCommand();
        $command->setConfig($this->configuration);
        $command->run($this->input, $this->output);

        $input = $this->createInput();
        $output = new Output();
        $command = new RollbackCommand();
        $command->setConfig($this->configuration);
        $command->run($input, $output);

        $messages = $output->getMessages();
        $this->assertTrue(is_array($messages));
        $this->assertArrayHasKey(0, $messages);
        $this->assertCount(6, $messages[0]);
        $this->assertArrayHasKey(OutputInterface::VERBOSITY_DEBUG, $messages);

        $input = $this->createInput();
        $output = new Output();
        $command = new RollbackCommand();
        $command->setConfig($this->configuration);
        $command->run($input, $output);

        $messages = $output->getMessages();
        $this->assertTrue(is_array($messages));
        $this->assertArrayHasKey(0, $messages);
        $this->assertCount(6, $messages[0]);
        $this->assertArrayHasKey(OutputInterface::VERBOSITY_DEBUG, $messages);
    }

    public function testRollbackAll()
    {
        $command = new MigrateCommand();
        $command->setConfig($this->configuration);
        $command->run($this->input, $this->output);

        $input = $this->createInput();
        $input->setOption('all', true);
        $output = new Output();
        $command = new RollbackCommand();
        $command->setConfig($this->configuration);
        $command->run($input, $output);

        $messages = $output->getMessages();
        $this->assertTrue(is_array($messages));
        $this->assertArrayHasKey(0, $messages);
        $this->assertArrayHasKey(OutputInterface::VERBOSITY_DEBUG, $messages);

        $input = $this->createInput();
        $output = new Output();
        $command = new RollbackCommand();
        $command->setConfig($this->configuration);
        $command->run($input, $output);

        $messages = $output->getMessages();
        $this->assertTrue(is_array($messages));
        $this->assertArrayHasKey(0, $messages);
        $this->assertEquals('<info>Nothing to rollback</info>' . "\n", $messages[0][1]);
        $this->assertArrayNotHasKey(OutputInterface::VERBOSITY_DEBUG, $messages);
    }

    public function testDryRun()
    {
        $command = new MigrateCommand();
        $command->setConfig($this->configuration);
        $command->run($this->input, $this->output);

        $input = $this->createInput();
        $output = new Output();
        $command = new RollbackCommand();
        $command->setConfig($this->configuration);
        $command->run($input, $output);

        $input = $this->createInput();
        $input->setOption('dry', true);
        $output = new Output();
        $command = new RollbackCommand();
        $command->setConfig($this->configuration);
        $command->run($input, $output);

        $dryMessages = $output->getMessages();
        $dryQueries = $dryMessages[OutputInterface::VERBOSITY_DEBUG];

        $this->assertTrue(is_array($dryMessages));
        $this->assertArrayHasKey(0, $dryMessages);
        $this->assertArrayHasKey(OutputInterface::VERBOSITY_DEBUG, $dryMessages);

        $input = $this->createInput();
        $output = new Output();
        $command = new RollbackCommand();
        $command->setConfig($this->configuration);
        $command->run($input, $output);

        $messages = $output->getMessages();
        $this->assertArrayHasKey(OutputInterface::VERBOSITY_DEBUG, $messages);
        $this->assertEquals($dryQueries, $messages[OutputInterface::VERBOSITY_DEBUG]);
    }

    public function testDryRunWithJsonOutput()
    {
        $command = new MigrateCommand();
        $command->setConfig($this->configuration);
        $command->run($this->input, $this->output);

        $input = $this->createInput();
        $input->setOption('dry', true);
        $input->setOption('output-format', 'json');
        $output = new Output();
        $command = new RollbackCommand();
        $command->setConfig($this->configuration);
        $command->run($input, $output);

        $messages = $output->getMessages(0);

        $this->assertTrue(is_array($messages));
        $this->assertCount(1, $messages);
        $this->assertArrayHasKey(0, $messages);
        $this->assertJson($messages[0]);

        $message = json_decode($messages[0], true);

        $this->assertArrayHasKey('executed_migrations', $message);
        $this->assertArrayHasKey('execution_time', $message);
        $this->assertNotEmpty($message['executed_migrations']);
        $this->assertNotEmpty($message['execution_time']);
        foreach ($message['executed_migrations'] as $executedMigration) {
            $this->assertArrayHasKey('classname', $executedMigration);
            $this->assertArrayHasKey('execution_time', $executedMigration);
            $this->assertArrayHasKey('executed_queries', $executedMigration);
        }
    }
}
