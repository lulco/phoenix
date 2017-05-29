## Change Log

### [Unreleased][unreleased]

#### Added
- dump command for creating migration from existing database
- structure introduced - all migrations are checked against actual database structure - possible BC if unknown column types are used

### [0.8.0] - 2017-05-02
#### Added
- json output for all commands
- migration datetime to Status command
- column settings class with constants

### [0.7.0] - 2017-04-06
#### Changed
- move all table methods (addColumn, addIndex, addForeignKey etc) from AbstractMigration to new Element MigrationTable which is now used in Query Builders
- migration dir in create command is required if there are more then one migration dir registered
- using serial and bigserial for autoincrement primary keys in pgsql instead of creating and dropping custom sequence

#### Added
- method getSettings to Column
- column types: tiny integer, small integer and medium integer, double, tinytext, mediumtext, longtext, tinyblob, mediumblob, blob, longblob, binary, varbinary, point, line, polygon

#### Fixed
- wrong order in rollback

#### Removed
- magic method variants addColumn and changeColumn from MigrationTable - possible BC if somebody uses methods addColumn(Column $column) or changeColumn($oldName, Column $column)

### [0.6.1] - 2016-12-13
#### Fixed
- support for changing column settings (allowNull, default) in pgsql

### [0.6.0] - 2016-09-26
#### Removed
- deprecated variants of methods addColumn and changeColumn which allowed set all settings individually as parameter

#### Fixed
- load configuration from all default config files (php, yml, neon, json) if no file is set as config option in command

#### Added
- support for json config file
- status command - list of migrations already executed and list of migrations to execute
- dry run - execute migrate or rollback command without real executing queries. Commands just print queries which would be executed
- enum and set column types

### [0.5.0] - 2016-08-03
#### Added
- column types date, bigint, float
- support for change charset in mysql (per table and also per column)
- possibility to create custom templates
- option "first" for migrate command
- option "all" for rollback command
- simple altering tables for pgsql: changes of column names, types and type casting for columns
- support for multi insert
- support for multi delete (IN condition)

#### Fixed
- several bugs in PdoAdapter
- output for executed queries in commands (Option -vvv)

### [0.4.0] - 2016-06-13
#### Added
- support for yaml and neon configs
- command execution time for each migration / rollback and total execution time
- method tableInfo for AdapterInterface
- support for changing columns in sqlite adapter

#### Updated
- composer libraries

### [0.3.0] - 2016-05-23
#### Added
- possibility to set custom name for index
- method drop index by name
- method select to Adapters
- added support for using DateTime instances in inserting / updating data

#### Changed
- automatically created names of indexes are now: idx_{tablename}_{implode('_', columns)} - possible BC
- boolean db type from int to tinyint in mysql
- minimal version of PHP to 5.6

### [0.2.0] - 2016-03-02
#### Added
- possibility to set position for column: after, first
- method changeColumn to migrations
- insert, update, delete methods

#### Fixed

#### Changed
- method Table::addIndex, now it accepts one parameter of type Index
- method Table::addForeignKey, now it accepts one parameter of type ForeignKey
- method addColumn accepts:
1. parameters name, type, allowNull, default, length, decimals, signed, autoincrement
1. array with keys: null, default, length, decimals, signed, autoincrement, after, first as 3rd parameter (name and type are still first two parameters)
1. object of type Column as only one parameter

### [0.1.1] - 2016-02-16
#### Added
- decimal type for MySQL
- rename table for all adapters

#### Fixed
- unsigned for MySQL

### [0.1.0] - 2016-02-05
- first tagged version
- 3 PDO Adapters: MySQL, PgSQL, SQLite
- supported methods in migrations: addColumn, addIndex, addForeignKey, dropColumn, dropIndex, dropForeignKey
- supported column types: string, integer, boolean, text, datetime, uuid, json, char

[unreleased]: https://github.com/lulco/phoenix/compare/0.8.0...HEAD
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
