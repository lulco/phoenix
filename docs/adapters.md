## Adapters

#### MySQL
```
'mysql' => [
	'adapter' => 'mysql',
	'db_name' => 'libs',
	'host' => 'localhost',
	'username' => 'root',
	'password' => '123',
	'charset' => 'utf8',
],
```
or dsn:
```
TODO
```

#### PostgreSQL
```
'pgsql' => [
	'adapter' => 'pgsql',
	'db_name' => 'libs',
	'host' => 'localhost',
	'username' => 'postgres',
	'password' => '123',
	'charset' => 'utf8',
],
```
or dsn:
```
TODO
```
#### SQLite

```
'sqlite_file' => [
	'adapter' => 'sqlite',
	'dsn' => 'sqlite:' . __DIR__ . '/phoenix.sqlite',
],
'sqlite_memory' => [
	'adapter' => 'sqlite',
	'dsn' => 'sqlite::memory:',
],
```
