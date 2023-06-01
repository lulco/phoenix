## Unique Constraints

You can create unique constraints in migration where new table is created.
```php
// create table with a unique constraint
$this->table('users')
    ->addColumn('username', 'string')
    ->addColumn('password', 'string')
    ->addColumn('created_at', 'datetime')
    ->addColumn('updated_at', 'datetime')
    ->addColumn('another_column', 'integer')
    ->addUniqueConstraint('username', 'u_username'));
    ->create();
```

Or add a unique constraint to an existing table same way:
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
    ->addUniqueConstraint('username', 'u_username'));
    ->save();
```

You can also specify a few columns to one unique constraint:
```php
// create table
$this->table('users')
    ->addColumn('username', 'string')
    ->addColumn('sku', 'string')
    ->addColumn('password', 'string')
    ->addColumn('created_at', 'datetime')
    ->addColumn('updated_at', 'datetime')
    ->addColumn('another_column', 'integer')
    ->addUniqueConstraint(['username', 'sku'], 'u_username_sku'));
    ->create();
```

Keep in mind that this is the preferred way to add a unique constraint (at least for [PostgreSQL](https://www.postgresql.org/docs/9.4/indexes-unique.html)) and NOT a unique index.
The use of indexes to enforce unique constraints could be considered an implementation detail that should not be accessed directly.


**One should, however, be aware that there's no need to manually create indexes on unique columns; doing so would just duplicate the automatically-created index.**