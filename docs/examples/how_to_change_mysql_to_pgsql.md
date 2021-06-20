## How to change mysql to pgsql or vice versa

Phoenix provides [dump command](../commands/dump_command.md) for dumping actual database structure to Phoenix's migration class. It also allows you to dump data.
This command can be used when you want to change your adapter from mysql to pgsql or vice versa. Just follow these steps.

- Delete all existing Phoenix migrations.

- Dump your actual database structure with data by running command:
```shell
php bin/phoenix dump --data
```

- Check and change migration if needed

- Then reconfigure your database connection from mysql to pgsql or vice versa 
  
- Run migrate command (it should execute just one migration - the one you just generate by dump command):
```shell
php bin/phoenix migrate
```

- Aaaand you are done :)
