## Migrations

### Create migration
To create new migration, use command [create](create_command.md). In all migrations you can setup several operations: create new tables, change existing tables and also rename or drop existing tables.

#### Create table
```php
$this->table('users')
    ->addColumn('username', 'string')
    ->addColumn('password', 'string')
    ->addColumn('created_at', 'datetime')
    ->addColumn('updated_at', 'datetime')
    ->addColumn('second_column', 'integer')
    ->addIndex('username', 'unique')
    ->create();
```

#### Change table
Add column, drop column, change column, add index, drop index, add foreign key, drop foreign key
```php
$this->table('users')
    ->addColumn('email', 'string')
    ->dropColumn('second_column')
    ->addIndex('email', 'unique')
    ->addForeignKey('self_fk', 'users', 'id')
    ->dropIndex('username')
    ->dropForeignKey('t1_fk')
    ->save();
```
#### Drop table
```php
$this->table('users')
    ->drop();
```

#### Rename table
```php
$this->table('users')
    ->rename('frontend_users');
```

#### Add an autoincrement primary column to an existing table
```php
$this->table('table_without_primary_key')
    ->addPrimaryColumns([new Column('id', 'integer', ['autoincrement' => true])])
    ->save();
```

#### Check if table exists
```php
if ($this->tableExists('users')) {
    // do something
}
```

#### Check if column in table exists
```php
if ($this->tableColumnExists('users', 'username') {
    // do something
}
```

#### Get Table structure
```php
$table = $this->getTable('users');
$usernameColumn = $table->getColumn('username');
```

#### Raw SQL
```php
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
```
