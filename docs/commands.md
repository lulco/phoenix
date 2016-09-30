## Commands
There are several commands in phoenix and they have few common options:
- `-h`, `--help` Displays help for command
- `-c`, `--config[=CONFIG]` Path to config file, if not set, phoenix is trying to find files phoenix.php, phoenix.yml, phoenix.neon, phoenix.json, first found is used
- `-t`, `--config-type[=CONFIG_TYPE]` Type of config, available values: php, yml, neon, json, if not set, phoenix identifies type from config file extension
- `-e`, `--environment=ENVIRONMENT` Environment from configuration to use, if not set, first environment from configuration is used
- `-v|vv|vvv`, `--verbose` Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
- `-q`, `--quiet` Do not output any message

### List of commands:
- [init](init_command.md)
- [create](create_command.md)
- [migrate](migrate_command.md)
- [rollback](rollback_command.md)
- [status](status_command.md)
- [cleanup](cleanup_command.md)
