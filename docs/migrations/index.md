## Migrations

### Create migration
To create new migration, use command [create](../commands/create_command.md). In all migrations you can setup several operations: create new tables, change existing tables and also rename, drop or truncate existing tables.

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

Read more about [primary keys](primary_keys.md).

#### Change table
Add column, drop column, rename column, change column, add index, drop index, add foreign key, drop foreign key
```php
$this->table('users')
    ->addColumn('email', 'string')
    ->dropColumn('second_column')
    ->renameColumn('asdf', 'alias')
    ->changeColumn('xyz', 'zyx', 'string')
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

#### Truncate table
```php
$this->table('users')
    ->truncate();
```

#### Rename table
```php
$this->table('users')
    ->rename('frontend_users');
```



#### Check if table exists
```php
if ($this->tableExists('users')) {
    // do something
}
```

#### Check if column in table exists
```php
if ($this->tableColumnExists('users', 'username')) {
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

### Fetch data
`$this->fetch` and `$this->fetchAll` accept the following arguments:
name|description|type|optional|default
-|-|-|-|-
table|The name of the table from which fetch the data|string|no|-
fields|A list of column names to return|array|yes|`['*']`
conditions|A list of condition, where the key of the associative array will be the column name and the value will be the compared value.<br><br>By default the operator of the comparison is '=' but you can specify the operator in the array key just after the column name preceded by a space (e.g. `column !=` or `column >`)|array|yes|`[]`
groups|A list of column names to group by|array|yes|`[]`

#### Fetch with conditions
```php
$trackableOrders = $this->fetchAll(
    'orders',
    [
        'id'
    ],
    [
        'user_id' => 12,
        'tracking_id !=' => null
    ]
);
```

```php
$orders = $this->fetchAll(
    'orders',
    [
        'id'
    ],
    [
        'user_id =' => 12,
        'price >=' => 23.50
    ]
);
```
