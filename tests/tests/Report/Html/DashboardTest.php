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
use SebastianBergmann\CodeCoverage\StaticAnalysis\Class_;
use SebastianBergmann\CodeCoverage\StaticAnalysis\LinesOfCode;
use SebastianBergmann\CodeCoverage\StaticAnalysis\Method;
use SebastianBergmann\CodeCoverage\StaticAnalysis\Visibility;

#[CoversClass(Dashboard::class)]
#[Small]
final class DashboardTest extends TestCase
{
    private string $outputFile = '';

    protected function tearDown(): void
    {
        if ($this->outputFile !== '' && is_file($this->outputFile)) {
            unlink($this->outputFile);
        }
    }

    public function testRendersClassesAndMethodsWithLinksToFileView(): void
    {
        $this->renderer()->render($this->directory(), $this->outputFile());

        $output = file_get_contents($this->outputFile);

        $this->assertNotFalse($output);

        $this->assertStringContainsString('<a href="Example.php.html#1">Example</a>', $output);
        $this->assertStringContainsString('<a href="Example.php.html#2">Example::doSomething</a>', $output);
    }

    public function testCannotRenderToFileThatCannotBeWritten(): void
    {
        $this->expectException(FileCouldNotBeWrittenException::class);

        $this->renderer()->render($this->directory(), $this->outputFile() . DIRECTORY_SEPARATOR . 'dashboard.html');
    }

    private function renderer(): Dashboard
    {
        return new Dashboard(
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

        $method = new Method('doSomething', 2, 4, 'public function doSomething(): void', Visibility::Public, 1);
        $class  = new Class_('Example', 'Example', '', 'Example.php', 1, 5, null, [], [], ['doSomething' => $method]);

        $root->addFile(
            new FileNode(
                'Example.php',
                $root,
                'abc123',
                [3 => [0 => 1]],
                [],
                [0 => ['name' => 'ExampleTest::testOne', 'size' => 'small', 'status' => 'passed', 'time' => 0.0]],
                ['Example' => $class],
                [],
                [],
                new LinesOfCode(5, 0, 5),
            ),
        );

        return $root;
    }
}
