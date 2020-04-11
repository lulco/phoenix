## Diff command
`diff [--ignore-tables IGNORE-TABLES] [-i|--indent INDENT] [--migration MIGRATION] [--dir DIR] [--template TEMPLATE] [-e|--environment ENVIRONMENT] [-c|--config CONFIG] [-t|--config_type CONFIG_TYPE] [-f|--output-format OUTPUT-FORMAT] [--] <source> <target>`

Creates new migration file with migration which is diff between source and target database. This command can be used when upgrading some system to newer version and you know the structure of both old and new version.

### Arguments
- `source` Source environment from config
- `target` Target environment from config

### Options:
- `--ignore-tables=IGNORE-TABLES` Comma separated list of tables to ignore (Structure and data). Default: phoenix_log
- `-i`, `--indent=INDENT` Indentation. Available values: 2spaces, 3spaces, 4spaces, 5spaces, tab [default: 4spaces]
- `--migration=MIGRATION` The name of migration. Default: Initialization
- `--dir=DIR` Directory to create migration in
- `--template=TEMPLATE` Path to template

All other options are common and they are described [here](commands.md).
