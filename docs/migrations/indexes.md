## Indexes

You can create index in migration where new table is created.
```php
// create table with index
$this->table('users')
    ->addColumn('username', 'string')
    ->addColumn('password', 'string')
    ->addColumn('created_at', 'datetime')
    ->addColumn('updated_at', 'datetime')
    ->addColumn('another_column', 'integer')
    ->addIndex('username', 'unique')
    ->create();
```

Or add index to an existing table same way:
```php
// create table
$this->table('users')
    ->addColumn('username', 'string')
    ->addColumn('password', 'string')
    ->addColumn('created_at', 'datetime')
    ->addColumn('updated_at', 'datetime')
    ->addColumn('another_column', 'integer')
    ->create();

// add index
$this->table('users')
    ->addIndex('username', 'unique')
    ->save();
```

You can also specify few settings to index columns - length and order:
```php
// create table
$this->table('users')
    ->addColumn('username', 'string')
    ->addColumn('password', 'string')
    ->addColumn('created_at', 'datetime')
    ->addColumn('updated_at', 'datetime')
    ->addColumn('another_column', 'integer')
    ->addIndex(new \Phoenix\Database\Element\IndexColumn('username', ['length' => 10]), 'unique')
    ->addIndex([new \Phoenix\Database\Element\IndexColumn('created_at', ['order' => 'DESC']), new \Phoenix\Database\Element\IndexColumn('updated_at', ['order' => 'ASC'])])
    ->create();
```

`order` allows specify ascending (ASC) or descending DESC index value storage. This setting works with PostgreSQL and MySQL >= 8.0.0, [you can use it in older versions but they are ignored](https://dev.mysql.com/doc/refman/5.7/en/create-index.html)
 
`length` setting allows you to set which part of string is used in index. It works with both MySQL and PostgreSQL - MySQL adapter uses native way of implementation and PostgreSQL uses SUBSTRING function.
