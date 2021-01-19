## FAQ

### Can I store my configuration file in other directory than project root?
Yes. Basically you have two options:
1. execute phoenix from project root with config option:
    - `php vendor/bin/phoenix {command} --config=/path/to/config/file`
2. execute phoenix from directory where your configuration file is stored:
    - `cd /path/to/config/`
    - `php ../../../vendor/bin/phoenix {command}`

### How can I turn off foreign keys check in migration?
Use method `checkForeignKeysOff()` and then `checkForeignKeysOn()` to turn it on again.

### How can I change collation for all existing tables and columns?
Create migration with single command:
```php
use Phoenix\Migration\AbstractMigration;

class ChangeCollation extends AbstractMigration
{
    protected function up(): void
    {
      $this->changeCollation('utf8mb4_general_ci');
    }
}
```
This will change collation of all tables and fields to `utf8mb4_general_ci` collation.
