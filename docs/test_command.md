## Test command
`php vendor/bin/phoenix test  [-e|--environment ENVIRONMENT] [-c|--config CONFIG] [-t|--config_type CONFIG_TYPE] [-f|--output-format OUTPUT-FORMAT] [--cleanup]`

Tests next migration by executing migrate, rollback, migrate for it.

### Options:
First four options are [common](commands.md), other are described here:
- `--cleanup` Cleanup after test (rollback migration at the end)
