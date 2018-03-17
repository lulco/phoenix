## Migrations

### Create migration
To create new migration, use command [create](create_command.md). In all migrations you can setup several operations: create new tables, change existing tables and also rename or drop existing tables.

#### Create table
```
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
```
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
```
$this->table('users')
    ->drop();
```

#### Rename table
```
$this->table('users')
    ->rename('frontend_users');
```

#### Add an autoincrement primary column to an existing table
```
$this->table('table_without_primary_key')
    ->addPrimaryColumns([new Column('id', 'integer', ['autoincrement' => true])])
    ->save();
```

#### Check if table exists
```
if ($this->tableExists('users')) {
    // do something
}
```

#### Check if column in table exists
```
if ($this->tableColumnExists('users', 'username') {
    // do something
}
```

#### Get Table structure
```
$table = $this->getTable('users');
$usernameColumn = $table->getColumn('username');
```
