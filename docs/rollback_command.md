## Rollback command
`php vendor/bin/phoenix rollback [-e|--environment ENVIRONMENT] [-c|--config [CONFIG]] [-t|--config_type [CONFIG_TYPE]] [--dry] [--all]`

Rollbacks migrations.

### Options:
First three options are [common](commands.md), other are described here:
- `--dry` Just print queries, no query defined in migration is executed
- `--all` Rollbacks all migrations, if not set only last executed migration is rollbacked
