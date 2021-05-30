# Phoenix
Framework agnostic database migrations for PHP.

[![PHP unit](https://github.com/lulco/phoenix/workflows/PHPunit/badge.svg)](https://github.com/lulco/phoenix/actions?query=workflow%3APHPunit)
[![PHPStan level](https://img.shields.io/badge/PHPStan-level:%20max-brightgreen.svg)](https://github.com/lulco/phoenix/actions?query=workflow%3A"PHP+static+analysis")
[![PHP static analysis](https://github.com/lulco/phoenix/workflows/PHP%20static%20analysis/badge.svg)](https://github.com/lulco/phoenix/actions?query=workflow%3A"PHP+static+analysis")
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/dd8723c4-85ea-4c28-b489-9cc7937264d0/mini.png)](https://insight.sensiolabs.com/projects/dd8723c4-85ea-4c28-b489-9cc7937264d0)
[![Latest Stable Version](https://img.shields.io/packagist/v/lulco/phoenix.svg)](https://packagist.org/packages/lulco/phoenix)
[![Total Downloads](https://img.shields.io/packagist/dt/lulco/phoenix.svg?style=flat-square)](https://packagist.org/packages/lulco/phoenix)
[![PHP 7 supported](http://php7ready.timesplinter.ch/lulco/phoenix/master/badge.svg)](https://github.com/lulco/phoenix/actions)

## Features
- Validation all settings in migration before executing first query
- Multiple migration directories
- Support for views
- Migrate up and down
- Print executed queries (in debug mode -vvv)
- Dry run - executing up or down migrations without real executing queries. Command just prints queries which will be executed in non-dry mode
- Add an autoincrement primary column to an existing table
- Dump command for creating migration from existing database
- Diff command for creating diff migration from two existing databases
- Test command for testing new migration executing migrate, rollback, migrate
- Status command that shows list of executed migrations and list of migrations to execute
- json output format for all commands
- Namespaces in migration classes
- Own migration templates
- Easy integration to any PHP application
- PHPStorm suggestions (works even better with deep-assoc-completion plugin)
- Change collation for all existing tables and columns
- Turn foreign keys check on / off in migration
- Simple autowiring in migrations

## Supported adapters
- MySql
- PostgreSQL

## Installation

### Composer
This library requires PHP 7.1 or later (7.2, 7.3, 7.4). It works also on PHP 8.0 and should work on PHP 8.1 too. The fastest and recommended way to install Phoenix is to add it to your project using Composer (https://getcomposer.org/).

```
composer require lulco/phoenix
```

## Usage

### [Create configuration file](docs/configuration/index.md)
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
            'port' => 3306, // optional
            'username' => 'user',
            'password' => 'pass',
            'db_name' => 'my_db',
            'charset' => 'utf8',
        ],
        'production' => [
            'adapter' => 'mysql',
            'host' => 'production_host',
            'port' => 3306, // optional
            'username' => 'user',
            'password' => 'pass',
            'db_name' => 'my_production_db',
            'charset' => 'utf8',
        ],
    ],
    'default_environment' => 'local',
    'log_table_name' => 'phoenix_log',
];
```
Read more about configuration [here](docs/configuration/index.md).

REMEMBER: migrations do some structure changes to the database, therefore the database user used for these migrations has to be able to do these changes.

### [Commands](docs/commands/index.md)
To run commands, use command runner `vendor/bin/phoenix` or `vendor/lulco/phoenix/bin/phoenix`.

##### Available commands:
- `init` - initialize phoenix
- `create` - create migration
- `migrate` - run migrations
- `rollback` - rollback migrations
- `dump` - create migration from existing database
- `status` - list of migrations already executed and list of migrations to execute
- `test` - test next migration by executing migrate, rollback, migrate for it
- `cleanup` - rollback all migrations and delete log table

You can run each command with `--help` option to get more information about it or read more [here](docs/commands/commands.md)

### [Init command](docs/commands/init_command.md)
Command `php vendor/bin/phoenix init` initializes phoenix and creates database table where executed migrations will be stored in. This command is executed automatically with first run of other commands, so you don't have to run it manually.

### [Create first migration](docs/commands/create_command.md)
Create command `php vendor/bin/phoenix create <migration> [<dir>]`

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
    protected function up(): void
    {
        
    }

    protected function down(): void
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
    protected function up(): void
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

Or you can use raw sql:
```php
<?php

namespace FirstDir;

use Phoenix\Migration\AbstractMigration;

class MyFirstMigration extends AbstractMigration
{
    protected function up(): void
    {
        $this->execute('CREATE TABLE `first_table` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `title` varchar(255) NOT NULL,
                `url` varchar(255) NOT NULL,
                `sorting` int(11) NOT NULL,
                `created_at` datetime NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `idx_first_table_url` (`url`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;'
        );
    }
}
```

Implementation of correspondent `down()` method which drops table `first_table` looks like below:
```php
    protected function down(): void
    {
        $this->table('first_table')
            ->drop();
    }
```

Now you can run `migrate` command to execute your first migration.

### [Migrate command](docs/commands/migrate_command.md)
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

If you want to rollback changes (e.g. you found out that you forgot add some column or index), you can run `rollback` command, update migration and then run `migrate` command again. Keep in mind that the best practice is to run `rollback` command before updating migration code.

### [Rollback command](docs/commands/rollback_command.md)
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

### [Dump command](docs/commands/dump_command.md)
Command `php vendor/bin/phoenix dump` dumps actual database structure into migration file.
If you don't use Phoenix yet and you have some tables in your database, this command helps you to start using Phoenix easier.

### [Diff command](docs/commands/diff_command.md)
Command `php vendor/bin/phoenix diff` creates migration as diff of two existing database structures.
This command can be used when upgrading some system to newer version and you know the structure of both old and new version. 

### [Status command](docs/commands/status_command.md)
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

### [Cleanup command](docs/commands/cleanup_command.md)
Cleanup command `php vendor/bin/phoenix cleanup` rollbacks all executed migrations and delete log table. After executing this command, the application is in state as before executing `init` command.

```
php bin/phoenix cleanup

Rollback for migration FirstDir\MyFirstMigration executed

Phoenix cleaned
```

### [Test command](docs/commands/test_command.md)
Test command `php vendor/bin/phoenix test` executes first next migration, then run rollback and migrate first migration again. This command is shortcut for executing commands:
```
php bin/phoenix migrate --first
php bin/phoenix rollback
php bin/phoenix migrate --first
```

Output looks like this:
```
php bin/phoenix test
Test started...

Migration FirstDir\MyFirstMigration executing...
Migration FirstDir\MyFirstMigration executed. Took 0.0456s

Rollback for migration FirstDir\MyFirstMigration executing...
Rollback for migration FirstDir\MyFirstMigration executed. Took 0.0105s

Migration FirstDir\MyFirstMigration executing...
Migration FirstDir\MyFirstMigration executed. Took 0.0378s

Test finished successfully

All done. Took 0.2840s
```
Read more about commands [here](docs/commands/commands.md)
