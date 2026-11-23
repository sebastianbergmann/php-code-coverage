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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\CodeCoverage\Data\ProcessedBranchCoverageData;
use SebastianBergmann\CodeCoverage\Data\ProcessedFunctionCoverageData;
use SebastianBergmann\CodeCoverage\FileCouldNotBeWrittenException;
use SebastianBergmann\CodeCoverage\Node\Directory as DirectoryNode;
use SebastianBergmann\CodeCoverage\Node\File as FileNode;
use SebastianBergmann\CodeCoverage\Report\Thresholds;
use SebastianBergmann\CodeCoverage\StaticAnalysis\AnalysisResult;
use SebastianBergmann\CodeCoverage\StaticAnalysis\LinesOfCode;
use SebastianBergmann\CodeCoverage\StaticAnalysis\SourceLines;
use SplFileInfo;

#[CoversClass(File::class)]
#[Small]
final class FileTest extends TestCase
{
    private string $directory;

    /**
     * @return non-empty-array<non-empty-string, array{non-empty-string, ?array<non-negative-int, positive-int>}>
     */
    public static function statementProvider(): array
    {
        return [
            'statement that was executed by small test'            => ['covered-by-small-tests', [0 => 1]],
            'statement that was executed by medium test'           => ['covered-by-medium-tests', [1 => 1]],
            'statement that was executed by large test'            => ['covered-by-large-tests', [2 => 1]],
            'statement that was executed by medium and small test' => ['covered-by-small-tests', [1 => 1, 0 => 1]],
            'statement that was executed by large and medium test' => ['covered-by-medium-tests', [2 => 1, 1 => 1]],
            'statement that was not executed'                      => ['danger', []],
            'statement that cannot be executed'                    => ['warning', null],
        ];
    }

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

    /**
     * Lines 13 and 14 are one statement of which only line 13 is executable.
     *
     * @param non-empty-string                       $expected
     * @param ?array<non-negative-int, positive-int> $tests
     */
    #[DataProvider('statementProvider')]
    #[TestDox('Shows line that is not executable like the statement it belongs to: $_dataName')]
    public function testShowsLineThatIsNotExecutableLikeTheStatementItBelongsTo(string $expected, ?array $tests): void
    {
        $this->renderer(false, false)->render(
            $this->fileWithStatements([13 => $tests], [13 => 1, 14 => 1]),
            $this->target(),
        );

        $html = file_get_contents($this->target() . '.html');

        $this->assertNotFalse($html);

        $this->assertStringContainsString(
            '<tr class="' . $expected . '"><td class="line-number"><a id="14" href="#14">14</a></td><td class="hit-count"></td>',
            $html,
        );
    }

    public function testDoesNotShowLineThatIsNotExecutableLikeAnotherStatement(): void
    {
        $this->renderer(false, false)->render(
            $this->fileWithStatements([13 => []], [13 => 1, 14 => 2]),
            $this->target(),
        );

        $html = file_get_contents($this->target() . '.html');

        $this->assertNotFalse($html);

        $this->assertStringContainsString('<tr class="danger"><td class="line-number"><a id="13"', $html);
        $this->assertStringContainsString('<tr><td class="line-number"><a id="14"', $html);
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
     * @param array<positive-int, ?array<non-negative-int, positive-int>> $lineCoverage
     * @param array<positive-int, int>                                    $linesOfStatements
     */
    private function fileWithStatements(array $lineCoverage, array $linesOfStatements): FileNode
    {
        return new FileNode(
            'BankAccount.php',
            new DirectoryNode(TEST_FILES_PATH),
            'abc123',
            $lineCoverage,
            [],
            [
                0 => ['name' => 'SmallTest::testOne', 'size' => 'small', 'status' => 'passed', 'time' => 0.0],
                1 => ['name' => 'MediumTest::testOne', 'size' => 'medium', 'status' => 'passed', 'time' => 0.0],
                2 => ['name' => 'LargeTest::testOne', 'size' => 'large', 'status' => 'passed', 'time' => 0.0],
            ],
            [],
            [],
            [],
            new LinesOfCode(35, 0, 35),
            false,
            false,
            null,
            SourceLines::fromAnalysisResult(
                new AnalysisResult(
                    [],
                    [],
                    [],
                    [],
                    new LinesOfCode(35, 0, 35),
                    $linesOfStatements,
                    [],
                    [],
                    [],
                ),
            ),
        );
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
