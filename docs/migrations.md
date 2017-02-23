## Migrations

### Create migration


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

#### Drop table

#### Rename table
