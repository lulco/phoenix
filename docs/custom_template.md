## Custom template

Custom templates can be used in create and dump command if option `--template` is set.
Template is simple text file where the migration structure is set. Default template looks like this:

```
<?php

###NAMESPACE###use Phoenix\Migration\AbstractMigration;

class ###CLASSNAME### extends AbstractMigration
{
###INDENT###protected function up()
###INDENT###{
###UP###
###INDENT###}

###INDENT###protected function down()
###INDENT###{
###DOWN###
###INDENT###}
}
```

As you can see, there are some special tokens which can be used:
- `###NAMESPACE###` - if migration name uses namespace, this token is replaced with string `namespace MigrationNamespace;`, if there is no namespace in migration name, this token is simply removed
- `###CLASSNAME###` - this token is replaced with migration class name
- `###INDENT###` - replaced with indentation, which can be set by option `-i`, `--indent=INDENT`. Default value is 4 spaces. Other possible values are: 2 spaces, 3 spaces, tab
- `###UP###` - replaced with up migration commands (in create command this is just replaced with 2 indentations)
- `###DOWN###` - replaced with down migration commands (in create command this is just replaced with 2 indentations)
