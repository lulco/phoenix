<?php

namespace Phoenix\Tests\Migration;

use Phoenix\Exception\PhoenixException;
use Phoenix\Migration\MigrationCreator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\Finder;

class MigrationCreatorTest extends TestCase
{
    public function testTemplatePathNotFound()
    {
        $this->expectException(PhoenixException::class);
        $this->expectExceptionMessage('Template "this-file-doesnt-exist" not found');
        new MigrationCreator('\Abc\Def', '    ', 'this-file-doesnt-exist');
    }

    public function testCreate()
    {
        $migrationCreator = new MigrationCreator('\Abc\Def', '    ');
        $migrationDir = __DIR__ . '/temp';
        $this->removeTempDir($migrationDir);
        mkdir($migrationDir);
        $migrationFullPath = $migrationCreator->create('', '', $migrationDir);

        $this->assertTrue(file_exists($migrationFullPath));

        $expected = <<<MIGRATION
<?php

namespace Abc;

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
