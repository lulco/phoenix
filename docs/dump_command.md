## Dump command
`php vendor/bin/phoenix dump [-d|--data] [--ignore-tables IGNORE-TABLES] [--ignore-data-tables IGNORE-DATA-TABLES] [-i|--indent INDENT] [-e|--environment ENVIRONMENT] [-c|--config CONFIG] [-t|--config_type CONFIG_TYPE] [-f|--output-format OUTPUT-FORMAT]`

Creates new migration file.

### Options:
- `-d`, `--data` Dump structure and also data
- `--ignore-tables[=IGNORE-TABLES]` Comma separaterd list of tables to ignore (Structure and data). Default: phoenix_log
- `--ignore-data-tables[=IGNORE-DATA-TABLES]` Comma separaterd list of tables which will be exported without data (Option `-d`, `--data` is required to use this option)
- `-i`, `--indent=INDENT` Indentation. Available values: 2spaces, 3spaces, 4spaces, 5spaces, tab [default: 4spaces]

All other options are common and they are described [here](commands.md).
