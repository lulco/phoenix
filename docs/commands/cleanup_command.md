## Cleanup command
`php vendor/bin/phoenix cleanup [-e|--environment ENVIRONMENT] [-c|--config CONFIG] [-t|--config_type CONFIG_TYPE] [-f|--output-format OUTPUT-FORMAT]`

This command rollbacks all executed migrations and delete log table. After executing this command, the application is in state as before executing `init` command.

All options are common and they are described [here](index.md).
