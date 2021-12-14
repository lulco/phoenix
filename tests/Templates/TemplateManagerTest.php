<?php

namespace Phoenix\Tests\Templates;

use Phoenix\Exception\PhoenixException;
use Phoenix\Migration\MigrationNameCreator;
use Phoenix\Templates\TemplateManager;
use PHPUnit\Framework\TestCase;

class TemplateManagerTest extends TestCase
{
    public function testTemplatePathNotFound()
    {
        $this->expectException(PhoenixException::class);
        $this->expectExceptionMessage('Template "this-file-doesnt-exist" not found');
        new TemplateManager(new MigrationNameCreator('\Abc\Def'), '    ', 'this-file-doesnt-exist');
    }

    public function testEmptyMigrationWithoutNamespace()
    {
        $templateManager = new TemplateManager(new MigrationNameCreator('Def'), '    ');

        $expected = <<<MIGRATION
<?php

use Phoenix\Migration\AbstractMigration;

class Def extends AbstractMigration
{
    protected function up(): void
    {

    }

    protected function down(): void
    {

    }
}

MIGRATION;

        $this->assertEquals($expected, $templateManager->createMigrationFromTemplate('', ''));
    }

    public function testEmptyWithNamespaceAndSpecialIndent()
    {
        $templateManager = new TemplateManager(new MigrationNameCreator('\Abc\Def'), 'asdf');

        $expected = <<<MIGRATION
<?php

namespace Abc;

use Phoenix\Migration\AbstractMigration;

class Def extends AbstractMigration
{
asdfprotected function up(): void
asdf{

asdf}

asdfprotected function down(): void
asdf{

asdf}
}

MIGRATION;

        $this->assertEquals($expected, $templateManager->createMigrationFromTemplate('', ''));
    }
}
