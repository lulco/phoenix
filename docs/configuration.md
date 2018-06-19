## Configuration
Before start using phoenix you need to create configuration file. If you don't want to set `--config` option to phoenix commands, name of your configuration file has to be one of phoenix.php, phoenix.yml, phoenix.neon or phoenix.json and it has to be stored at the same directory as you execute phoenix from, e.g. project root.

Basically configuration is an array which is passed to phoenix commands. Files with extensions yml (yaml), neon and json are parsed, php configuration file is included. It means that php file has to return an configuration array directly.

Configuration array consist of four parts:
- `log_table_name` - string - default "phoenix_log"
- `migration_dirs` - array - list of migration directories where migrations can be stored in. Array keys are used as directory identifier, values are paths to directories.
- `environments` - array - list of environment where migrations are executed, e.g. local, staging, production etc.
- `default_environment` - string - environment which is used in phoenix command if `--environment` option is not set. It has to be one of keys from `environments` array. If no `default_environment` is set, first of `environments` is used.

### Example
Let's say you want to create configuration file, where `log_table_name` is "my_phoenix_log", you have two `migration_dirs` (first and second, which are located in the same directory as configuration file), also two `environments` both uses mysql adapter, and your `default_environment` is "local". Now we show you, how this config looks like using different type of configuration files:

#### phoenix.php
```php
<?php
return [
    'log_table_name' => 'my_phoenix_log',
    'migration_dirs' => [
        'first' => __DIR__ . '/first_dir',
        'second' => __DIR__ . '/second_dir',
    ],
    'environments' => [
        'local' => [
            'adapter' => 'mysql',
            'host' => 'localhost',
            'username' => 'user',
            'password' => 'pass',
            'db_name' => 'my_db',
            'charset' => 'utf8',
        ],
        'production' => [
            'adapter' => 'mysql',
            'host' => 'production_host',
            'username' => 'user',
            'password' => 'pass',
            'db_name' => 'my_production_db',
            'charset' => 'utf8',
        ],
    ],
    'default_environment ' => 'local',
];
```
As you know and as you can see, in php configuration file you can use `__DIR__` as token for identifying actual directory (directory where configuration file is stored). In other types of configuration files you can use special token `%%ACTUAL_DIR%%` which is replaced with path to configuration file directory.

#### phoenix.yml or phoenix.yaml
```
log_table_name: my_phoenix_log
migration_dirs:
  first: '%%ACTUAL_DIR%%/first'
  second: '%%ACTUAL_DIR%%/second'
environments:
  local:
    adapter: mysql
    host: localhost
    username: user
    password: pass
    db_name: my_db
    charset: utf8
  production
    adapter: mysql
    host: production_host
    username: user
    password: pass
    db_name: my_production_db
    charset: utf8
default_environment: local
```

#### phoenix.neon
```
log_table_name: my_phoenix_log
migration_dirs:
    first: '%%ACTUAL_DIR%%/first'
    second: '%%ACTUAL_DIR%%/second'
environments:
    local:
        adapter: mysql
        host: localhost
        username: user
        password: pass
        db_name: my_db
        charset: utf8
    production
        adapter: mysql
        host: production_host
        username: user
        password: pass
        db_name: my_production_db
        charset: utf8
default_environment: local
```

Configuration files of types yml and neon are pretty similar. The only difference is that in yml type you have to use 2 or 4 spaces for indentation, but no tabs, while in neon type the tabs are allowed.

#### phoenix.json
```
{
    "log_table_name": "my_phoenix_log",
    "migration_dirs": {
        "first": "%%ACTUAL_DIR%%/first",
        "second": "%%ACTUAL_DIR%%/second"
    },
    "environments": {
        "local": {
            "adapter": "mysql",
            "host": "localhost",
            "username": "user",
            "password": "pass",
            "db_name": "my_db",
            "charset": "utf8"
        },
        "production": {
            "adapter": "mysql",
            "host": "production_host",
            "username": "user",
            "password": "pass",
            "db_name": "my_production_db",
            "charset": "utf8"
        }
    },
    "default_environment": "local"
}
```
