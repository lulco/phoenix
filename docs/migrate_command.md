## Migrate command
`php vendor/bin/phoenix migrate [-e|--environment ENVIRONMENT] [-c|--config CONFIG] [-t|--config_type CONFIG_TYPE] [-f|--output-format OUTPUT-FORMAT] [--dry] [--target TARGET] [--first] [--dir=DIR] [--class=CLASS]`

Executes available migrations.

### Options:
First four options are [common](commands.md), other are described here:
- `--dry` Just print queries, no query defined in migration is executed
- `--first` Executes only first available migration, if not set all available migrations are executed. This option is ignored if `--target` is used.
- `--target=TARGET` Executes only migrations with datetime less then or equal to TARGET. If not full datetime is passed, the rest spaces will be filled with zeros e.g. `--target=2020` will be executed as `--target=20200000000000`. If this option is used, `--first` is ignored. This option can be used in debugging or in [deployment process](deploy.md)
- `--dir=DIR` Executes only migrations in dir(s) (multiple values allowed)
- `--class=CLASS` Executes only migrations specified by this option (multiple values allowed)
