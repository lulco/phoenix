## Migrate command
`php vendor/bin/phoenix migrate [-e|--environment ENVIRONMENT] [-c|--config CONFIG] [-t|--config_type CONFIG_TYPE] [-f|--output-format OUTPUT-FORMAT] [--dry] [--first] [--dir=DIR] [--class=CLASS]`

Executes available migrations.

### Options:
First four options are [common](commands.md), other are described here:
- `--dry` Just print queries, no query defined in migration is executed
- `--first` Executes only first available migration, if not set all available migrations are executed
- `--dir=DIR` Executes only migrations in dir(s) (multiple values allowed)
- `--class=CLASS` Executes only migrations specified by this option (multiple values allowed)
