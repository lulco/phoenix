<?php

declare(strict_types=1);

namespace Phoenix\Tests\Migration;

use Phoenix\Exception\PhoenixException;
use Phoenix\Migration\MigrationCreator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\Finder;

final class MigrationCreatorTest extends TestCase
{
    public function testTemplatePathNotFound(): void
    {
        $this->expectException(PhoenixException::class);
        $this->expectExceptionMessage('Template "this-file-doesnt-exist" not found');
        new MigrationCreator('\Abc\Def', '    ', 'this-file-doesnt-exist');
    }

    public function testCreate(): void
    {
        $migrationCreator = new MigrationCreator('\Abc\Def', '    ');
        $migrationDir = __DIR__ . '/temp';
        $this->removeTempDir($migrationDir);
        mkdir($migrationDir);
        $migrationFullPath = $migrationCreator->create('', '', $migrationDir);

        $this->assertTrue(file_exists($migrationFullPath));

        $expected = <<<MIGRATION
<?php

declare(strict_types=1);

namespace Abc;

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

        $this->assertEquals($expected, file_get_contents($migrationFullPath));
        $this->removeTempDir($migrationDir);
    }

    private function removeTempDir(string $dir): void
    {
        if (file_exists($dir)) {
            $files = Finder::create()->files()->in($dir);
            foreach ($files as $file) {
                unlink((string)$file);
            }
            rmdir($dir);
        }
    }
}
