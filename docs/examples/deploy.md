## Deploy

Phoenix can be used in deployment process. When application is deployed, migrations can be executed.

We can parse the default output or use json output which we can store to the variable (recommended):
```php
echo $migrations = shell_exec('php bin/phoenix migrate -f json');
echo "\n\n";
```

Then the deploy continues with all its jobs. If deploy is successfully finished, we should do nothing.
But if some error occurred, we should revert all migrations executed in this deployment process.

So now we can read executed migrations from variable:
```php
$executedMigrations = json_decode($migrations, true);
```

Then we will find datetime of the first executed migration.
```php
$target = $executedMigrations[0]['datetime'] ?? null;
```

We want to rollback all migrations. So datetime of the first executed migration is the target for rollback command:
```php
echo shell_exec('php bin/phoenix rollback --target ' . $target);
```
