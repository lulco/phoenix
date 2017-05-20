## Create command
`php vendor/bin/phoenix create [-e|--environment ENVIRONMENT] [-c|--config [CONFIG]] [-t|--config_type [CONFIG_TYPE]] [-f|--output-format OUTPUT-FORMAT] [--template [TEMPLATE]] [-i|--indent [INDENT]] <migration> [<dir>]`

Creates new migration file.

### Options:
First four options are [common](commands.md), other are described here:
- `--template` Path to custom migration template file, if not set, default template file is used
- `-i`, `--indent[=INDENT]` Indentation. Available values: 2spaces, 3spaces, 4spaces, 5spaces, tab [default: 4spaces]

### Arguments:
- `migration` Migration name - PHP class name, namespace can be used
- `dir` Key of [migration directory](migration_directories.md) where migration will be stored, if not set, first [migration directory](migration_directories.md) is used
