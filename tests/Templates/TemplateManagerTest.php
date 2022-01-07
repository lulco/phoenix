<?php

declare(strict_types=1);

namespace Phoenix\Tests\Templates;

use Phoenix\Exception\PhoenixException;
use Phoenix\Migration\MigrationNameCreator;
use Phoenix\Templates\TemplateManager;
use PHPUnit\Framework\TestCase;

final class TemplateManagerTest extends TestCase
{
    public function testTemplatePathNotFound(): void
    {
        $this->expectException(PhoenixException::class);
        $this->expectExceptionMessage('Template "this-file-doesnt-exist" not found');
        new TemplateManager(new MigrationNameCreator('\Abc\Def'), '    ', 'this-file-doesnt-exist');
    }

    public function testEmptyMigrationWithoutNamespace(): void
    {
        $templateManager = new TemplateManager(new MigrationNameCreator('Def'), '    ');

        $expected = <<<MIGRATION
<?php

declare(strict_types=1);

use Phoenix\Migration\AbstractMigration;

final class Def extends AbstractMigration
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

    public function testEmptyWithNamespaceAndSpecialIndent(): void
    {
        $templateManager = new TemplateManager(new MigrationNameCreator('\Abc\Def'), 'asdf');

        $expected = <<<MIGRATION
<?php

declare(strict_types=1);

namespace Abc;

use Phoenix\Migration\AbstractMigration;

final class Def extends AbstractMigration
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
