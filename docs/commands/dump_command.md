## Dump command
`php vendor/bin/phoenix dump [-d|--data] [--ignore-tables IGNORE-TABLES] [--ignore-data-tables IGNORE-DATA-TABLES] [-i|--indent INDENT] [-e|--environment ENVIRONMENT] [-c|--config CONFIG] [-t|--config_type CONFIG_TYPE] [-f|--output-format OUTPUT-FORMAT]`

Creates new migration file from actual database structure. If you don't use Phoenix yet and you have some tables in your database, this command helps you to start using Phoenix easier.
If you want to start using Phoenix when database already exists, it is recommended to add additional check for each table:
```php
if ($this->tableExists('table_name')) {
    $this->table('table_name')
        -> ...
        ->create();
}
``` 

### Options:
- `-d`, `--data` Dump structure and also data
- `--ignore-tables=IGNORE-TABLES` Comma separated list of tables to ignore (Structure and data). Default: phoenix_log
- `--ignore-data-tables=IGNORE-DATA-TABLES` Comma separated list of tables which will be exported without data (Option `-d`, `--data` is required to use this option)
- `-i`, `--indent=INDENT` Indentation. Available values: 2spaces, 3spaces, 4spaces, 5spaces, tab [default: 4spaces]
- `--migration=MIGRATION` The name of migration. Default: Initialization
- `--dir=DIR` Directory to create migration in
- `--template=TEMPLATE` Path to template

All other options are common and they are described [here](index.md).
