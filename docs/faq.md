## FAQ

### Can I store my configuration file in other directory than project root?
Yes. Basically you have two options:
1. execute phoenix from project root with config option:
    - `php vendor/bin/phoenix {command} --config=/path/to/config/file`
2. execute phoenix from directory where your configuration file is stored:
    - `cd /path/to/config/`
    - `php ../../../vendor/bin/phoenix {command}`
