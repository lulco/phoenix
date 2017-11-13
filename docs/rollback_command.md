## Rollback command
`php vendor/bin/phoenix rollback [-e|--environment ENVIRONMENT] [-c|--config CONFIG] [-t|--config_type CONFIG_TYPE] [-f|--output-format OUTPUT-FORMAT] [--dry] [--all] [--dir=DIR]`

Rollbacks migrations.

### Options:
First four options are [common](commands.md), other are described here:
- `--dry` Just print queries, no query defined in migration is executed
- `--all` Rollbacks all migrations, if not set only last executed migration is rollbacked
- `--dir` Executes only migrations in dir(s)
