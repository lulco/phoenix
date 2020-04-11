## Namespaces

Phoenix supports namespaces in migrations. Each migration can have different namespace (also in the same directory) or it can be without namespace at all.

You can generate migration four ways:
1) `php vendor/bin/phoenix create MyFirstMigration`. Class with name `MyFirstMigration` will be created. No namespace will be generated.
1) `php vendor/bin/phoenix create MyFirstMigration` which creates migration without namespace and you can add namespace to class manually (before executing it).
1) `php vendor/bin/phoenix create "MyNamespace\MyFirstMigration"` (migration name is between quotation marks: `" "`). This command creates migration class `MyFirstMigration` with namespace `MyNamespace`.
1) `php vendor/bin/phoenix create MyNamespace\\MyFirstMigration` (migration name contains two backslashes `\\`). As previous, this command creates migration class `MyFirstMigration` with namespace `MyNamespace`.
