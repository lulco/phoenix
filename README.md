# Phoenix
Framework agnostic database migrations for PHP.

[![Build Status](https://travis-ci.org/lulco/phoenix.svg?branch=master)](https://travis-ci.org/lulco/phoenix)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/lulco/phoenix/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/lulco/phoenix/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/lulco/phoenix/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/lulco/phoenix/?branch=master)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/dd8723c4-85ea-4c28-b489-9cc7937264d0/mini.png)](https://insight.sensiolabs.com/projects/dd8723c4-85ea-4c28-b489-9cc7937264d0)
[![Latest Stable Version](https://img.shields.io/packagist/v/lulco/phoenix.svg)](https://packagist.org/packages/lulco/phoenix)
[![Total Downloads](https://img.shields.io/packagist/dt/lulco/phoenix.svg?style=flat-square)](https://packagist.org/packages/lulco/phoenix)
[![Dependency Status](https://www.versioneye.com/user/projects/575d6bbd7757a00041b3b85b/badge.svg?style=flat)](https://www.versioneye.com/user/projects/575d6bbd7757a00041b3b85b)

## Features
- Validation all settings in migration before executing first query
- Multiple migration directories
- Migrate up and down
- Namespaces
- Own migration templates
- Easy integration to any PHP application

## Supported adapters
- MySql
- PostgreSQL
- SQLite

## Instalation

### Composer
This library requires PHP 5.6 or later. It works also on PHP 7.0 and HHVM. The fastest and recommended way to install Phoenix is to add it to your project using Composer (https://getcomposer.org/).

```
composer require lulco/phoenix
```

## Usage

### Create configuration file
Create file `phoenix.php` in the root directory of your project. For example:
```
<?php

return [
    'migration_dirs' => [
        'first_dir' => __DIR__ . '/../first_dir',
        'second_dir' => __DIR__ . '/../second_dir',
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
            'host' => 'prod_host',
            'username' => 'user',
            'password' => 'pass',
            'db_name' => 'my_prod_db',
            'charset' => 'utf8',
        ],
    ],
];
```

### Create first migration

Use command `vendor/bin/phoenix` or `vendor/lulco/phoenix/bin/phoenix`
```
php vendor/bin/phoenix create "FirstDir\MyFirstMigration"
```
This will create PHP class `FirstDir\MyFirstMigration` in file named `{timestamp}_my_first_migration.php` where `{timestamp}` representing actual timestamp in format `YmdHis` e.g. `20160919082117`




