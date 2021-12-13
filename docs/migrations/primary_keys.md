## Primary keys

There are several ways how to add primary key when creating table:

Default is autoincrement column with name id:
```php
$this->table('users')
    ->addColumn('username', 'string')
    ->addColumn('password', 'string')
    ->addColumn('created_at', 'datetime')
    ->addColumn('updated_at', 'datetime')
    ->create();
```

You can use one or more of defined columns - second argument of method `table()` is string or array of strings - names of columns:
```php
$this->table('users', 'identifier')
    ->addColumn('identifier', 'string')
    ->addColumn('username', 'string')
    ->addColumn('password', 'string')
    ->addColumn('created_at', 'datetime')
    ->addColumn('updated_at', 'datetime')
    ->create();
```

Or you can define new column(s) here. These columns will be added to the table:
```php
$this->table('users', new \Phoenix\Database\Element\Column('identifier', 'string'))
    ->addColumn('username', 'string')
    ->addColumn('password', 'string')
    ->addColumn('created_at', 'datetime')
    ->addColumn('updated_at', 'datetime')
    ->create();
```

If you don't want to add primary key to the table, use `false` or `null`:
```php
$this->table('users', false)
    ->addColumn('username', 'string')
    ->addColumn('password', 'string')
    ->addColumn('created_at', 'datetime')
    ->addColumn('updated_at', 'datetime')
    ->create();
```

For dropping primary key, just use method `dropPrimaryKey()` in migration:
```php
$this->table('users')
    ->dropPrimaryKey()
    ->save();
```

Phoenix also allows you to add primary key(s) to existing table.

Here is an example how to add primary key which is autoincrement integer
```php
$this->table('table_without_primary_key')
    ->addPrimaryColumns([new \Phoenix\Database\Element\Column('id', 'integer', ['autoincrement' => true])])
    ->save();
```

Of course you can setup any type of primary key. For example some uuid column and set the value of new column for each row:
```php
$this->table('table_without_primary_key')
    ->addPrimaryColumns([new \Phoenix\Database\Element\Column('identifier', 'uuid')], function (array $row) {
        $row['identifier'] = (string)\Ramsey\Uuid\UuidFactory::uuid4();
        return $row;
    })
    ->save();
```
