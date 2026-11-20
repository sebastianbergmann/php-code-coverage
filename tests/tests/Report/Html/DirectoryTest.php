<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\Report\Html;

use const DIRECTORY_SEPARATOR;
use function file_get_contents;
use function is_file;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\CodeCoverage\FileCouldNotBeWrittenException;
use SebastianBergmann\CodeCoverage\Node\Directory as DirectoryNode;
use SebastianBergmann\CodeCoverage\Node\File as FileNode;
use SebastianBergmann\CodeCoverage\Report\Thresholds;
use SebastianBergmann\CodeCoverage\StaticAnalysis\LinesOfCode;

#[CoversClass(Directory::class)]
#[Small]
final class DirectoryTest extends TestCase
{
    private string $outputFile = '';

    protected function tearDown(): void
    {
        if ($this->outputFile !== '' && is_file($this->outputFile)) {
            unlink($this->outputFile);
        }
    }

    public function testRendersDirectoriesBeforeFilesAndBothSortedByName(): void
    {
        $this->renderer()->render($this->directory(), $this->outputFile());

        $output = file_get_contents($this->outputFile);

        $this->assertNotFalse($output);

        $this->assertMatchesRegularExpression(
            '/href="a\/C\.php\.html".*href="b\/C\.php\.html".*href="A\.php\.html".*href="B\.php\.html"/s',
            $output,
        );
    }

    public function testCannotRenderToFileThatCannotBeWritten(): void
    {
        $this->expectException(FileCouldNotBeWrittenException::class);

        $this->renderer()->render($this->directory(), $this->outputFile() . DIRECTORY_SEPARATOR . 'index.html');
    }

    private function renderer(): Directory
    {
        return new Directory(
            __DIR__ . '/../../../../src/Report/Html/Renderer/Template/',
            'test-generator',
            'Jan 1 00:00:00 UTC 2026',
            Thresholds::default(),
            false,
            false,
        );
    }

    private function outputFile(): string
    {
        $this->outputFile = tempnam(sys_get_temp_dir(), 'cov_');

        return $this->outputFile;
    }

    private function directory(): DirectoryNode
    {
        $root = new DirectoryNode('root');

        $root->addFile($this->file('B.php', $root));
        $root->addFile($this->file('A.php', $root));

        foreach (['b', 'a'] as $name) {
            $directory = $root->addDirectory($name);

            $directory->addFile($this->file('C.php', $directory));
        }

        return $root;
    }

    /**
     * @param non-empty-string $name
     */
    private function file(string $name, DirectoryNode $parent): FileNode
    {
        return new FileNode(
            $name,
            $parent,
            'abc123',
            [1 => [0 => 1], 2 => []],
            [],
            [0 => ['name' => 'ExampleTest::testOne', 'size' => 'small', 'status' => 'passed', 'time' => 0.0]],
            [],
            [],
            [],
            new LinesOfCode(2, 0, 2),
        );
    }
}
