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
use const ENT_COMPAT;
use function file_get_contents;
use function htmlspecialchars;
use function is_dir;
use function mkdir;
use function rmdir;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;
use FilesystemIterator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\CodeCoverage\Data\ProcessedBranchCoverageData;
use SebastianBergmann\CodeCoverage\Data\ProcessedFunctionCoverageData;
use SebastianBergmann\CodeCoverage\FileCouldNotBeWrittenException;
use SebastianBergmann\CodeCoverage\Node\Directory as DirectoryNode;
use SebastianBergmann\CodeCoverage\Node\File as FileNode;
use SebastianBergmann\CodeCoverage\Report\Thresholds;
use SebastianBergmann\CodeCoverage\StaticAnalysis\LinesOfCode;
use SplFileInfo;

#[CoversClass(File::class)]
#[Small]
final class FileTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('php-code-coverage-html-file-test-', true);

        mkdir($this->directory);
    }

    protected function tearDown(): void
    {
        foreach (new FilesystemIterator($this->directory) as $fileInfo) {
            if (!$fileInfo instanceof SplFileInfo) {
                continue; // @codeCoverageIgnore
            }

            if ($fileInfo->isDir()) {
                rmdir($fileInfo->getPathname());

                continue;
            }

            unlink($fileInfo->getPathname());
        }

        rmdir($this->directory);
    }

    public function testRendersViewForLineCoverageOnlyWhenBranchCoverageIsNotAvailable(): void
    {
        $this->renderer(false, false)->render($this->file(), $this->target());

        $this->assertFileExists($this->target() . '.html');
        $this->assertFileDoesNotExist($this->target() . '_branch.html');
        $this->assertFileDoesNotExist($this->target() . '_path.html');
    }

    public function testCannotRenderViewForLineCoverageToFileThatCannotBeWritten(): void
    {
        mkdir($this->target() . '.html');

        $this->expectException(FileCouldNotBeWrittenException::class);

        $this->renderer(true, true)->render($this->file(), $this->target());
    }

    public function testCannotRenderViewForBranchCoverageToFileThatCannotBeWritten(): void
    {
        mkdir($this->target() . '_branch.html');

        try {
            $this->renderer(true, true)->render($this->file(), $this->target());
        } catch (FileCouldNotBeWrittenException) {
            $this->assertFileExists($this->target() . '.html');
            $this->assertFileDoesNotExist($this->target() . '_path.html');

            return;
        }

        $this->fail('Expected exception was not thrown');
    }

    public function testCannotRenderViewForPathCoverageToFileThatCannotBeWritten(): void
    {
        mkdir($this->target() . '_path.html');

        try {
            $this->renderer(true, true)->render($this->file(), $this->target());
        } catch (FileCouldNotBeWrittenException) {
            $this->assertFileExists($this->target() . '.html');
            $this->assertFileExists($this->target() . '_branch.html');
            $this->assertTrue(is_dir($this->target() . '_path.html'));

            return;
        }

        $this->fail('Expected exception was not thrown');
    }

    /**
     * The branch of BankAccount::setBalance() that ends with the decision on
     * line 13 has two edges that lead out of it, but it is not known for
     * either of them whether it was taken.
     */
    public function testDoesNotRenderMarkersForDecisionOfWhichNoOutcomeIsKnown(): void
    {
        $file = $this->file(
            [
                'BankAccount->setBalance' => new ProcessedFunctionCoverageData(
                    [
                        0 => new ProcessedBranchCoverageData(0, 4, 11, 13, [0 => 1], [0 => 1, 1 => 2], []),
                        1 => new ProcessedBranchCoverageData(5, 8, 14, 14, [0 => 1], [], []),
                        2 => new ProcessedBranchCoverageData(9, 13, 16, 16, [], [], []),
                    ],
                    [],
                ),
            ],
        );

        $this->renderer(true, false)->render($file, $this->target());

        $branch = file_get_contents($this->target() . '_branch.html');

        $this->assertNotFalse($branch);

        $this->assertStringContainsString(
            '<a id="13" href="#13">13</a></td><td class="hit-count"></td>',
            $branch,
        );

        $this->assertStringNotContainsString('class="branch-markers', $branch);

        $this->assertStringContainsString(
            htmlspecialchars(
                '<p class="popover-heading">Outcomes of branch #1</p>' .
                '<ul class="branch-outcomes">' .
                '<li><span class="branch-unknown" aria-hidden="true">&ndash;</span> <a href="#14">#2 (L14)</a>: not reported</li>' .
                '<li><span class="branch-unknown" aria-hidden="true">&ndash;</span> <a href="#16">#3 (L16)</a>: not reported</li>' .
                '</ul>',
                ENT_COMPAT,
            ),
            $branch,
        );
    }

    private function renderer(bool $hasBranchCoverage, bool $hasPathCoverage): File
    {
        return new File(
            __DIR__ . '/../../../../src/Report/Html/Renderer/Template/',
            'test-generator',
            'Jan 1 00:00:00 UTC 2026',
            Thresholds::default(),
            $hasBranchCoverage,
            $hasPathCoverage,
        );
    }

    private function target(): string
    {
        return $this->directory . DIRECTORY_SEPARATOR . 'BankAccount.php';
    }

    /**
     * @param array<non-empty-string, ProcessedFunctionCoverageData> $functionCoverage
     */
    private function file(array $functionCoverage = []): FileNode
    {
        return new FileNode(
            'BankAccount.php',
            new DirectoryNode(TEST_FILES_PATH),
            'abc123',
            [13 => [0 => 1], 14 => [0 => 1], 16 => []],
            $functionCoverage,
            [0 => ['name' => 'BankAccountTest::testBalanceCanBeSet', 'size' => 'small', 'status' => 'passed', 'time' => 0.0]],
            [],
            [],
            [],
            new LinesOfCode(35, 0, 35),
            $functionCoverage !== [],
        );
    }
}
