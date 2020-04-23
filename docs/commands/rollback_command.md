## Rollback command
`php vendor/bin/phoenix rollback [-e|--environment ENVIRONMENT] [-c|--config CONFIG] [-t|--config_type CONFIG_TYPE] [-f|--output-format OUTPUT-FORMAT] [--dry] [--all] [--dir=DIR] [--class=CLASS]`

Rollbacks migrations.

### Options:
First four options are [common](index.md), other are described here:
- `--dry` Just print queries, no query defined in migration is executed
- `--all` Rollbacks all migrations, if not set only last executed migration is rollbacked. This option is ignored if `--target` is used.
- `--target=TARGET` Executes only migrations with datetime greater then or equal to TARGET. If not full datetime is passed, the rest spaces will be filled with zeros e.g. `--target=2020` will be executed as `--target=20200000000000`. If this option is used, `--all` is ignored. This option can be used in debugging or in [deployment process](../examples/deploy.md)
- `--dir=DIR` Executes only migrations in dir(s) (multiple values allowed)
- `--class=CLASS` Executes only migrations specified by this option (multiple values allowed)
