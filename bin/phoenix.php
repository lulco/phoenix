#!/usr/bin/env php
<?php

$autoloader = require __DIR__ . '/../src/composer_autoloader.php';

if (!$autoloader()) {
    die(
      'You need to set up the project dependencies using the following commands:' . PHP_EOL .
      'curl -s http://getcomposer.org/installer | php' . PHP_EOL .
      'php composer.phar install' . PHP_EOL
    );
}

use Phoenix\Command\CleanupCommand;
use Phoenix\Command\InitCommand;
use Phoenix\Command\MigrateCommand;
use Phoenix\Command\RollbackCommand;
use Symfony\Component\Console\Application;

$application = new Application();
$application->add(new InitCommand());
$application->add(new CleanupCommand());
$application->add(new MigrateCommand());
$application->add(new RollbackCommand());
$application->run();
