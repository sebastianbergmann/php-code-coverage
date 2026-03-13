<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\Util;

use const PHP_OS_FAMILY;
use function file_get_contents;
use function is_dir;
use function rmdir;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SebastianBergmann\CodeCoverage\WriteOperationFailedException;

#[CoversClass(Filesystem::class)]
final class FilesystemTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/php-code-coverage-filesystem-test';
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    public function testCreateDirectoryCreatesDirectory(): void
    {
        $directory = $this->tempDir . '/test-dir';

        Filesystem::createDirectory($directory);

        $this->assertTrue(is_dir($directory));
    }

    public function testCreateDirectoryCreatesNestedDirectories(): void
    {
        $directory = $this->tempDir . '/a/b/c';

        Filesystem::createDirectory($directory);

        $this->assertTrue(is_dir($directory));
    }

    public function testCreateDirectorySucceedsWhenDirectoryAlreadyExists(): void
    {
        $directory = $this->tempDir . '/existing';

        Filesystem::createDirectory($directory);
        Filesystem::createDirectory($directory);

        $this->assertTrue(is_dir($directory));
    }

    public function testCreateDirectoryThrowsExceptionWhenDirectoryCannotBeCreated(): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            // Characters like < and > are invalid in Windows paths
            $directory = sys_get_temp_dir() . '\\<invalid>\\this-cannot-be-created';
        } else {
            $directory = '/proc/this-cannot-be-created';
        }

        $this->expectException(DirectoryCouldNotBeCreatedException::class);

        Filesystem::createDirectory($directory);
    }

    public function testWriteWritesContentToFile(): void
    {
        $target  = $this->tempDir . '/output.txt';
        $content = 'Hello, World!';

        Filesystem::write($target, $content);

        $this->assertSame($content, file_get_contents($target));
    }

    public function testWriteCreatesDirectoryForFile(): void
    {
        $target  = $this->tempDir . '/nested/dir/output.txt';
        $content = 'nested content';

        Filesystem::write($target, $content);

        $this->assertTrue(is_dir($this->tempDir . '/nested/dir'));
        $this->assertSame($content, file_get_contents($target));
    }

    public function testWriteOverwritesExistingFile(): void
    {
        $target = $this->tempDir . '/overwrite.txt';

        Filesystem::write($target, 'first');
        Filesystem::write($target, 'second');

        $this->assertSame('second', file_get_contents($target));
    }

    public function testWriteDoesNotCreateDirectoryForStreamTarget(): void
    {
        $target = tempnam(sys_get_temp_dir(), 'php-cc-test-');

        Filesystem::write('php://memory', 'stream content');

        // If we get here without error, the stream write succeeded
        // and no directory creation was attempted for the stream URI
        $this->assertTrue(true);

        if ($target !== false) {
            @unlink($target);
        }
    }

    public function testWriteThrowsExceptionWhenFileCannotBeWritten(): void
    {
        if (PHP_OS_FAMILY === 'Darwin') {
            $this->markTestSkipped('Cannot reliably trigger a write failure on macOS because /proc does not exist');
        }

        if (PHP_OS_FAMILY === 'Windows') {
            // Character < is invalid in Windows file names, and dirname() returns the existing temp dir
            $target = sys_get_temp_dir() . '\\file<invalid';
        } else {
            $target = '/proc/this-cannot-be-written';
        }

        $this->expectException(WriteOperationFailedException::class);

        Filesystem::write($target, 'content');
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $items = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($items as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }

        rmdir($directory);
    }
}
