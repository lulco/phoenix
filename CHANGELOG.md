# Change Log

## [Unreleased][unreleased]

### Fixed
- decimal, float, double default values

## [2.6.0] - 2024-07-19
### Added
- support for Symfony 7

## [2.5.0] - 2023-12-08
### Added
- support for PHP 8.3

### Fixed
- default for integer column types (Thanks to [fagai](https://github.com/fagai))

## [2.4.0] - 2023-06-01
### Added
- unique constraints support (Thanks to [Anton Khainak](https://github.com/aksafan))

## [2.3.0] - 2022-11-08
### Added
- support for PHP 8.2

### Fixed
- creates migration dir if doesn't exist

## [2.2.0] - 2022-04-26
### Added
- support for default collation for mysql in config

## [2.1.0] - 2022-04-14
### Added
- config options template and indent
- method tableIndexExists (Thanks to [Anton Pobis](https://github.com/tonop01))

## [2.0.0] - 2022-01-24
### Changed
- use utf8mb4 as default charset for mysql (fix but BC break, use e.g. `$this->changeCollation('utf8mb4_general_ci')` to change all tables and fields to it)
- dropped support for unsupported PHP versions and added native typehints (BC break)
- changed autoload to PSR-4
  - moved namespace Dumper to Phoenix\Dumper (BC break)
  - moved namespace Comparator to Phoenix\Comparator (BC break)
- added `declare(strict_types=1);` to all classes
- all classes which can be final are final (BC break if there are some extensions)
- all methods which can be final are final
- moved default command names from configure to __construct

### Removed
- class MysqlWithJsonQueryBuilder (BC break)
- method setName from AbstractCommand (BC break - if setName() is called after name is already set, it will be changed)
- dropped support symfony libs (console, finder and yaml) 3.x and 4.x (BC break)

### Added
- visibility for constants
- support for symfony libs (console, finder and yaml) 6.x

## [1.12.0] - 2022-01-07
### Added
- support MySql 8 and PostgreSQL 14

### Fixed
- table with no primary keys in Dumper

## [1.11.1] - 2021-12-07
### Fixed
- removed phpspec/prophecy from misused replace in `composer.json`

## [1.11.0] - 2021-12-06
### Added
- support for PHP 8.1
- support for `CURRENT_TIMESTAMP` for columns with type `datetime` (Thanks to [Jared Cheney](https://github.com/jaredc))

## [1.10.0] - 2021-08-23
### Added
- timestamptz (timestamp with time zone) column type for pgsql (Thanks to [Taichi Inaba](https://github.com/chatii))

## [1.9.1] - 2021-08-18
### Fixed
- Mysql: Use FIRST in combination with autoincrement generates wrong query

## [1.9.0] - 2021-07-07
### Added
- support for different operators in `$conditions` array of PdoAdapter methods (Thanks to [Giuliano Collacchioni](https://github.com/Kal-Aster))
- support for views

### Fixed
- readme link (Thanks to [Niek Oost](https://github.com/niekoost))

## [1.8.0] - 2021-06-01
### Added
- year column type (year for mysql, numeric(4) for pgsql)
- new options add-table-exists-check and auto-increment to Dumper command
- documentation for primary keys
- step by step tutorial for using dump command to change mysql to pgsql or vice versa

### Fixed
- single quotes in comments
- table comment in dump
- transfer tinyint(1) to boolean in mysql only if it has default values 1 or 0 
- dump command skip everything which is not of type "BASE TABLE" (VIEW, SYSTEM VIEW etc.)
- dumping special values (null, true, false, strings with apostrophe)

## [1.7.0] - 2021-04-14
### Added
- simple autowiring in migrations

## [1.6.0] - 2021-04-03
### Added
- bit column type (Thanks to [Slava Ershov](https://github.com/fishus))

### Fixed
- PHP 8.1 deprecation notice (Thanks to [Daniel Opitz](https://github.com/odan))
- escaping column value in Dumper (Thanks to [Slava Ershov](https://github.com/fishus))
- nullable timestamp field with default value
- migrate / rollback commands option --class will work without starting backslash

## [1.5.0] - 2021-01-25
### Changed
- moved tests from travis to github actions and removed scrutinizer
- improved code applying phpstan

### Added
- support to change collation on all existing tables and columns
- support for turn on / off checking foreign keys in migration

## [1.4.0] - 2020-12-01
### Changed
- default value of boolean columns is set to false if it is not null (this prevent from errors when user forgot set default to false)
- better organized docs

### Added
- support for PHP 8.0
  - `$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);`
- set autoincrement for new table and also for existing table

### Fixed
- escape string in QueryBuilderInterface

## [1.3.0] - 2020-04-16
### Added
- method renameColumn() to migrations
- PHPStorm meta for better suggestions
- migration datetime to json output of migrate / rollback commands for future purpose
- smallserial as primary in pqsql
- method truncate() to migrations
- settings order and length to index columns
- DiffCommand for creating migration from diff of two databases or migration and database
- target option for migrate and rollback commands

### Changed
- unfreezed symfony/console and added versions 3.4.31 and 4.3.4 to conflict

## [1.2.0] - 2020-03-16
### Changed
- changed comparation `==` to `===` and added strict parameter to in_array

### Added
- support for `CURRENT_TIMESTAMP` for columns with type `timestamp`
- support for json column type for newer versions of mysql (>= 5.7.8) (To keep using text instead of json, force version in [config](docs/configuration/index.md))

### Fixed
- tests on travis

## [1.1.1] - 2019-09-12
### Changed
- freezed symfony/console to >=3.1 <3.4.31|>=4.0 <4.3.4

### Added
- support for nette/neon ^3.0

## [1.1.0] - 2019-02-11
### Added
- time column type (Thanks to [Tibor Mikóczy](https://github.com/mikoczy))
- timestamp column type

## [1.0.0] - 2018-06-20
### Changed
- added return type (:void) for up() and down() methods in AbstractMigration (BC Break - Fix: add return type :void to all migrations)
- replaced nette/finder with symfony/finder
- PDOAdapter::execute splitted into PDOAdapter::execute(PDOStatement $sql) and PDOAdapter::query(string $sql)

### Removed
- removed support for sqlite adapter
- removed nette/utils

### Fixed
- typehint for fetch method

## [0.13.0] - 2018-03-14
### Changed
- second parameter of methods fetchAll and fetch `string $fields = '*'` has been changed to `array $fields = ['*']` (BC Break - fix: change string to array)

### Added
- methods tableExists, tableColumnExists and getTable to migration
- posibility to add an autoincrement primary key on an existing table

### Fixed
- add a primary key on an existing table

## [0.12.1] - 2017-12-18
### Fixed
- binding for null and boolean values

## [0.12.0] - 2017-12-06
### Changed
- dropped support for PHP 5.6, PHP 7.0 and hhvm
- dropped support for symfony/console ^2.8 and added support for symfony/console ^4.0 and symfony/yaml ^4.0
- added typehints

## [0.11.0] - 2017-11-14
### Added
- option to execute all migrations from selected directory(-ies) in migrate and rollback command
- option to execute migrations via classname in migrate and rollback command
- TestCommand to test next migration via migrate-rollback-migrate(-rollback)

## [0.10.0] - 2017-10-01
### Added
- method copyTable for copy table structure, data or both
- interactive choice of migration dirs in CreateCommand
- comment for table
- comment for column
- PHP 7.2 compatibility

### Changed
- renameTable refactoring

### Fixed
- ucfirst for lowercase named migrations

## [0.9.1] - 2017-06-07
### Fixed
- reverted calling execute instead of run for InitCommand in AbstractCommand

## [0.9.0] - 2017-06-07
### Changed
- default action for migration table is now alter instead of create - possible BC, use `->create()` for new tables

### Added
- dump command for creating migration from existing database
- structure introduced - all migrations are checked against actual database structure - possible BC if unknown column types are used

### Fixed
- command options config, config_type, template and indent require value
- typehints in MigrationTable

## [0.8.0] - 2017-05-02
### Added
- json output for all commands
- migration datetime to Status command
- column settings class with constants

## [0.7.0] - 2017-04-06
### Changed
- move all table methods (addColumn, addIndex, addForeignKey etc) from AbstractMigration to new Element MigrationTable which is now used in Query Builders
- migration dir in create command is required if there are more then one migration dir registered
- using serial and bigserial for autoincrement primary keys in pgsql instead of creating and dropping custom sequence

### Added
- method getSettings to Column
- column types: tiny integer, small integer and medium integer, double, tinytext, mediumtext, longtext, tinyblob, mediumblob, blob, longblob, binary, varbinary, point, line, polygon

### Fixed
- wrong order in rollback

### Removed
- magic method variants addColumn and changeColumn from MigrationTable - possible BC if somebody uses methods addColumn(Column $column) or changeColumn($oldName, Column $column)

## [0.6.1] - 2016-12-13
### Fixed
- support for changing column settings (allowNull, default) in pgsql (Thanks to [Tibor Mikóczy](https://github.com/mikoczy))

## [0.6.0] - 2016-09-26
### Removed
- deprecated variants of methods addColumn and changeColumn which allowed set all settings individually as parameter

### Fixed
- load configuration from all default config files (php, yml, neon, json) if no file is set as config option in command

### Added
- support for json config file
- status command - list of migrations already executed and list of migrations to execute
- dry run - execute migrate or rollback command without real executing queries. Commands just print queries which would be executed
- enum and set column types

## [0.5.0] - 2016-08-03
### Added
- column types date, bigint, float
- support for change charset in mysql (per table and also per column)
- possibility to create custom templates
- option "first" for migrate command
- option "all" for rollback command
- simple altering tables for pgsql: changes of column names, types and type casting for columns
- support for multi insert
- support for multi delete (IN condition)

### Fixed
- several bugs in PdoAdapter
- output for executed queries in commands (Option -vvv)

## [0.4.0] - 2016-06-13
### Added
- support for yaml and neon configs
- command execution time for each migration / rollback and total execution time
- method tableInfo for AdapterInterface
- support for changing columns in sqlite adapter

### Updated
- composer libraries

## [0.3.0] - 2016-05-23
### Added
- possibility to set custom name for index
- method drop index by name
- method select to Adapters
- added support for using DateTime instances in inserting / updating data

### Changed
- automatically created names of indexes are now: idx_{tablename}_{implode('_', columns)} - possible BC
- boolean db type from int to tinyint in mysql
- minimal version of PHP to 5.6

## [0.2.0] - 2016-03-02
### Added
- possibility to set position for column: after, first
- method changeColumn to migrations
- insert, update, delete methods

### Fixed

### Changed
- method Table::addIndex, now it accepts one parameter of type Index
- method Table::addForeignKey, now it accepts one parameter of type ForeignKey
- method addColumn accepts:
1. parameters name, type, allowNull, default, length, decimals, signed, autoincrement
1. array with keys: null, default, length, decimals, signed, autoincrement, after, first as 3rd parameter (name and type are still first two parameters)
1. object of type Column as only one parameter

## [0.1.1] - 2016-02-16
### Added
- decimal type for MySQL
- rename table for all adapters

### Fixed
- unsigned for MySQL

## [0.1.0] - 2016-02-05
- first tagged version
- 3 PDO Adapters: MySQL, PgSQL, SQLite
- supported methods in migrations: addColumn, addIndex, addForeignKey, dropColumn, dropIndex, dropForeignKey
- supported column types: string, integer, boolean, text, datetime, uuid, json, char

[unreleased]: https://github.com/lulco/phoenix/compare/2.6.0...HEAD
[2.6.0]: https://github.com/lulco/phoenix/compare/2.5.0...2.6.0
[2.5.0]: https://github.com/lulco/phoenix/compare/2.4.0...2.5.0
[2.4.0]: https://github.com/lulco/phoenix/compare/2.3.0...2.4.0
[2.3.0]: https://github.com/lulco/phoenix/compare/2.2.0...2.3.0
[2.2.0]: https://github.com/lulco/phoenix/compare/2.1.0...2.2.0
[2.1.0]: https://github.com/lulco/phoenix/compare/2.0.0...2.1.0
[2.0.0]: https://github.com/lulco/phoenix/compare/1.12.0...2.0.0
[1.12.0]: https://github.com/lulco/phoenix/compare/1.11.1...1.12.0
[1.11.1]: https://github.com/lulco/phoenix/compare/1.11.0...1.11.1
[1.11.0]: https://github.com/lulco/phoenix/compare/1.10.0...1.11.0
[1.10.0]: https://github.com/lulco/phoenix/compare/1.9.1...1.10.0
[1.9.1]: https://github.com/lulco/phoenix/compare/1.9.0...1.9.1
[1.9.0]: https://github.com/lulco/phoenix/compare/1.8.0...1.9.0
[1.8.0]: https://github.com/lulco/phoenix/compare/1.7.0...1.8.0
[1.7.0]: https://github.com/lulco/phoenix/compare/1.6.0...1.7.0
[1.6.0]: https://github.com/lulco/phoenix/compare/1.5.0...1.6.0
[1.5.0]: https://github.com/lulco/phoenix/compare/1.4.0...1.5.0
[1.4.0]: https://github.com/lulco/phoenix/compare/1.3.0...1.4.0
[1.3.0]: https://github.com/lulco/phoenix/compare/1.2.0...1.3.0
[1.2.0]: https://github.com/lulco/phoenix/compare/1.1.1...1.2.0
[1.1.1]: https://github.com/lulco/phoenix/compare/1.1.0...1.1.1
[1.1.0]: https://github.com/lulco/phoenix/compare/1.0.0...1.1.0
[1.0.0]: https://github.com/lulco/phoenix/compare/0.13.0...1.0.0
[0.13.0]: https://github.com/lulco/phoenix/compare/0.12.1...0.13.0
[0.12.1]: https://github.com/lulco/phoenix/compare/0.12.0...0.12.1
[0.12.0]: https://github.com/lulco/phoenix/compare/0.11.0...0.12.0
[0.11.0]: https://github.com/lulco/phoenix/compare/0.10.0...0.11.0
[0.10.0]: https://github.com/lulco/phoenix/compare/0.9.1...0.10.0
[0.9.1]: https://github.com/lulco/phoenix/compare/0.9.0...0.9.1
[0.9.0]: https://github.com/lulco/phoenix/compare/0.8.0...0.9.0
[0.8.0]: https://github.com/lulco/phoenix/compare/0.7.0...0.8.0
[0.7.0]: https://github.com/lulco/phoenix/compare/0.6.1...0.7.0
[0.6.1]: https://github.com/lulco/phoenix/compare/0.6.0...0.6.1
[0.6.0]: https://github.com/lulco/phoenix/compare/0.5.0...0.6.0
[0.5.0]: https://github.com/lulco/phoenix/compare/0.4.0...0.5.0
[0.4.0]: https://github.com/lulco/phoenix/compare/0.3.0...0.4.0
[0.3.0]: https://github.com/lulco/phoenix/compare/0.2.0...0.3.0
[0.2.0]: https://github.com/lulco/phoenix/compare/0.1.1...0.2.0
[0.1.1]: https://github.com/lulco/phoenix/compare/0.1.0...0.1.1
[0.1.0]: https://github.com/lulco/phoenix/compare/0.0.0...0.1.0
