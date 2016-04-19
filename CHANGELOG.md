## Change Log

### [Unreleased][unreleased]
#### Added
- possibility to set custom name for index
- method drop index by name
- method select to Adapters

#### Changed
- automatically created names of indexes are now: idx_{tablename}_{implode('_', columns)} - possible BC
- boolean db type from int to tinyint in mysql

### [0.2.0] - 2016/03/02
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

### [0.1.1] - 2016/02/16
#### Added
- decimal type for MySQL
- rename table for all adapters

#### Fixed
- unsigned for MySQL

### [0.1.0] - 5.02.2016
- First tagged version
- 3 PDO Adapters: MySQL, PgSQL, SQLite
- supported methods in migrations: addColumn, addIndex, addForeignKey, dropColumn, dropIndex, dropForeignKey
- supported column types: string, integer, boolean, text, datetime, uuid, json, char

[unreleased]: https://git.efabrica.sk/web-components/article-storage/compare/0.2.0...HEAD
[0.2.0]: https://github.com/lulco/phoenix/compare/0.1.1...0.2.0
[0.1.1]: https://github.com/lulco/phoenix/compare/0.1.0...0.1.1
[0.1.0]: https://github.com/lulco/phoenix/compare/0.0.0...0.1.0
