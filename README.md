# Phoenix
Framework agnostic database migrations for PHP.

[![Build Status](https://travis-ci.org/lulco/phoenix.svg?branch=master)](https://travis-ci.org/lulco/phoenix)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/lulco/phoenix/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/lulco/phoenix/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/lulco/phoenix/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/lulco/phoenix/?branch=master)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/dd8723c4-85ea-4c28-b489-9cc7937264d0/mini.png)](https://insight.sensiolabs.com/projects/dd8723c4-85ea-4c28-b489-9cc7937264d0)
[![Latest Stable Version](https://img.shields.io/packagist/v/lulco/phoenix.svg)](https://packagist.org/packages/lulco/phoenix)
[![Total Downloads](https://img.shields.io/packagist/dt/lulco/phoenix.svg?style=flat-square)](https://packagist.org/packages/lulco/phoenix)
[![Dependency Status](https://www.versioneye.com/user/projects/575d6bbd7757a00041b3b85b/badge.svg?style=flat)](https://www.versioneye.com/user/projects/575d6bbd7757a00041b3b85b)
[![PHP 7 supported](http://php7ready.timesplinter.ch/lulco/phoenix/master/badge.svg)](https://travis-ci.org/lulco/phoenix)
[![HHVM supported](http://hhvm.h4cc.de/badge/lulco/phoenix.svg)](https://travis-ci.org/lulco/phoenix)

## Features
- Validation all settings in migration before executing first query
- Multiple migration directories
- Migrate up and down
- Print executed queries (in debug mode -vvv)
- Dry run - executing up or down migrations without real executing queries. Commands just print queries which will be executed in non-dry mode
- Dump command for creating migration from existing database
- json output format for all commands
- Namespaces in migration classes
- Own migration templates
- Easy integration to any PHP application

## Supported adapters
- MySql
- PostgreSQL
- SQLite

## Installation

### Composer
This library requires PHP 5.6 or later. It works also on PHP 7.0, PHP 7.1, PHP 7.2 and HHVM. The fastest and recommended way to install Phoenix is to add it to your project using Composer (https://getcomposer.org/).

```
composer require lulco/phoenix
```

## Usage

### Create configuration file
Create file `phoenix.php` in the root directory of your project. For example:
```php
<?php

return [
    'migration_dirs' => [
        'first' => __DIR__ . '/../first_dir',
        'second' => __DIR__ . '/../second_dir',
    ],
    'environments' => [
        'local' => [
            'adapter' => 'mysql',
            'host' => 'localhost',
            'username' => 'user',
            'password' => 'pass',
            'db_name' => 'my_db',
            'charset' => 'utf8',
        ],
        'production' => [
            'adapter' => 'mysql',
            'host' => 'production_host',
            'username' => 'user',
            'password' => 'pass',
            'db_name' => 'my_production_db',
            'charset' => 'utf8',
        ],
    ],
];
```

### Commands
To run commands, use command runner `vendor/bin/phoenix` or `vendor/lulco/phoenix/bin/phoenix`.

##### Available commands:
- `init` - initialize phoenix
- `create` - create migration
- `migrate` - run migrations
- `rollback` - rollback migrations
- `dump` - create migration from existing database
- `status` - list of migrations already executed and list of migrations to execute
- `cleanup` - rollback all migrations and delete log table

You can run each command with `--help` option to get more information about it.

### Init command
Command `php vendor/bin/phoenix init` initializes phoenix and creates database table where executed migrations will be stored in. This command is executed automatically with first run of other commands, so you don't have to run it manually.

### Create first migration
Create command `php vendor/bin/phoenix create [options] [--] <migration> [<dir>]`

```
php vendor/bin/phoenix create "FirstDir\MyFirstMigration" second
```
This will create PHP class `FirstDir\MyFirstMigration` in file named `{timestamp}_my_first_migration.php` where `{timestamp}` represents actual timestamp in format `YmdHis` e.g. `20160919082117`. This file will be created in migration directory `second` which is configured as `__DIR__ . '/../second_dir'` (see configuration example above).

`create` command creates a skeleton of migration file, which looks like this:
```php
<?php

namespace FirstDir;

use Phoenix\Migration\AbstractMigration;

class MyFirstMigration extends AbstractMigration
{
    protected function up()
    {
        
    }

    protected function down()
    {
        
    }
}
```

Now you need to implement both methods: `up()`, which is used when command `migrate` is executed and `down()`, which is used when command `rollback` is executed. In general: if you create table in `up()` method, you have to drop this table in `down()` method and vice versa.

Let say you need to execute this query:
```sql
CREATE TABLE `first_table` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `url` varchar(255) NOT NULL,
  `sorting` int(11) NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_first_table_url` (`url`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

You need to implement `up()` method in your migration class as below:
```php
<?php

namespace FirstDir;

use Phoenix\Database\Element\Index;
use Phoenix\Migration\AbstractMigration;

class MyFirstMigration extends AbstractMigration
{
    protected function up()
    {
        $this->table('first_table')
            ->addColumn('title', 'string')
            ->addColumn('url', 'string')
            ->addColumn('sorting', 'integer')
            ->addColumn('created_at', 'datetime')
            ->addIndex('url', Index::TYPE_UNIQUE)
            ->create();
    }
}
```
Implementation of correspondent `down()` method which drops table `first_table` looks like below:
```php
    protected function down()
    {
        $this->table('first_table')
            ->drop();
    }
```

Now you can run `migrate` command to execute your first migration.

### Migrate command
Migrate command `php vendor/bin/phoenix migrate` executes all available migrations. In this case you will see output like this:
```
php vendor/bin/phoenix migrate

Migration FirstDir\MyFirstMigration executing
Migration FirstDir\MyFirstMigration executed. Took 0.0308s

All done. Took 0.0786s
```

If you run this command again, there will be no migrations to execute, so the output looks like this:

```
php vendor/bin/phoenix migrate

Nothing to migrate

All done. Took 0.0451s
```

If you want to rollback changes (e.g. you found out that you forgot add some column or index), you can run `rollback` command, update migration and then run `migrate` command again. Keep in mind that the best practice is to run `rollback` command before updating migration.

### Rollback command
Rollback command `php vendor/bin/phoenix rollback` rollbacks last executed migration. In this case you will see output like this:
```
php vendor/bin/phoenix rollback

Rollback for migration FirstDir\MyFirstMigration executing
Rollback for migration FirstDir\MyFirstMigration executed. Took 0.0108s

All done. Took 0.0594s
```

If you run this command again, there will be no migrations to rollback, so the output looks like this:
```
php vendor/bin/phoenix rollback

Nothing to rollback

All done. Took 0.0401s
```

### Dump command
Command `php vendor/bin/phoenix dump [options]` dumps actual database structure into migration file.
If you don't use Phoenix yet and you have some tables in your database, this command helps you to start using Phoenix easier.

### Status command
Run `php vendor/bin/phoenix status` and show list of migrations already executed and list of migrations to execute. Output is like this:

```
Executed migrations
+--------------------+---------------------------------------------+---------------------+
| Migration datetime | Class name                                  | Executed at         |
+--------------------+---------------------------------------------+---------------------+
| 20160919082117     | FirstDir\MyFirstMigration                   | 2016-09-26 06:49:49 |
+--------------------+---------------------------------------------+---------------------+

Migrations to execute
+--------------------+---------------------------------+
| Migration datetime | Class name                      |
+--------------------+---------------------------------+
| 20160921183201     | FirstDir\MySecondMigration      |
+--------------------+---------------------------------+

All done. Took 0.2016s
```

### Cleanup command
Cleanup command `php vendor/bin/phoenix cleanup` rollbacks all executed migrations and delete log table. After executing this command, the application is in state as before executing `init` command.

```
php bin/phoenix cleanup

Rollback for migration FirstDir\MyFirstMigration executed

Phoenix cleaned
```
