## Migrate command
`php vendor/bin/phoenix migrate [-e|--environment ENVIRONMENT] [-c|--config CONFIG] [-t|--config_type CONFIG_TYPE] [-f|--output-format OUTPUT-FORMAT] [--dry] [--first]`

Executes available migrations.

### Options:
First four options are [common](commands.md), other are described here:
- `--dry` Just print queries, no query defined in migration is executed
- `--first` Executes only first available migration, if not set all available migrations are executed
