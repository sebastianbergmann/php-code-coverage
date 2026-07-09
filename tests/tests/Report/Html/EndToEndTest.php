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
use const PHP_EOL;
use function file_get_contents;
use function file_put_contents;
use function iterator_count;
use function mkdir;
use function str_replace;
use FilesystemIterator;
use PHPUnit\Framework\Attributes\CoversNamespace;
use PHPUnit\Framework\Attributes\Medium;
use RegexIterator;
use SebastianBergmann\CodeCoverage\Data\ProcessedCodeCoverageData;
use SebastianBergmann\CodeCoverage\Data\RawCodeCoverageData;
use SebastianBergmann\CodeCoverage\Node\Builder;
use SebastianBergmann\CodeCoverage\StaticAnalysis\FileAnalyser;
use SebastianBergmann\CodeCoverage\StaticAnalysis\ParsingSourceAnalyser;
use SebastianBergmann\CodeCoverage\TestCase;
use SplFileInfo;

#[CoversNamespace('SebastianBergmann\CodeCoverage\Report\Html')]
#[Medium]
final class EndToEndTest extends TestCase
{
    protected function tearDown(): void
    {
        $this->removeTemporaryFiles();
    }

    public function testLineCoverageForBankAccountTest(): void
    {
        $expectedFilesPath = TEST_FILES_PATH . 'Report' . DIRECTORY_SEPARATOR . 'HTML' . DIRECTORY_SEPARATOR . 'CoverageForBankAccount';

        $report = new Facade;
        $report->process($this->getLineCoverageForBankAccount()->getReport(), TEST_FILES_PATH . 'tmp');

        $this->assertFilesEquals($expectedFilesPath, TEST_FILES_PATH . 'tmp');
    }

    public function testMissingBranchCoverageDataIsMarkedInDirectorySummary(): void
    {
        $report = new Facade;
        $report->process($this->reportWithFileWithoutBranchCoverageData(), TEST_FILES_PATH . 'tmp');

        $index = file_get_contents(TEST_FILES_PATH . 'tmp' . DIRECTORY_SEPARATOR . 'index.html');

        $this->assertNotFalse($index);
        $this->assertStringContainsString('Not all files have branch and path coverage data', $index);
    }

    public function testTestSizeAndStatusAreReflectedInSourceRendering(): void
    {
        $report = new Facade;
        $report->process($this->coverageForBankAccountWithVariousTestSizesAndStatuses()->getReport(), TEST_FILES_PATH . 'tmp');

        $source = file_get_contents(TEST_FILES_PATH . 'tmp' . DIRECTORY_SEPARATOR . 'BankAccount.php.html');

        $this->assertNotFalse($source);
        $this->assertStringContainsString('covered-by-medium-tests', $source);
        $this->assertStringContainsString('covered-by-small-tests', $source);
    }

    public function testTestSizeIsReflectedInBranchAndPathSourceRendering(): void
    {
        $report = new Facade;
        $report->process($this->pathCoverageForBankAccountWithVariousTestSizes()->getReport(), TEST_FILES_PATH . 'tmp');

        $branch = file_get_contents(TEST_FILES_PATH . 'tmp' . DIRECTORY_SEPARATOR . 'BankAccount.php_branch.html');
        $path   = file_get_contents(TEST_FILES_PATH . 'tmp' . DIRECTORY_SEPARATOR . 'BankAccount.php_path.html');

        $this->assertNotFalse($branch);
        $this->assertNotFalse($path);
        $this->assertStringContainsString('covered-by-medium-tests', $branch);
        $this->assertStringContainsString('covered-by-small-tests', $path);
    }

    public function testPartialCoverageIsReflectedInBranchAndPathRendering(): void
    {
        $report = new Facade;
        $report->process($this->pathCoverageForBankAccountWithPartialBranchAndPathCoverage()->getReport(), TEST_FILES_PATH . 'tmp');

        $branch = file_get_contents(TEST_FILES_PATH . 'tmp' . DIRECTORY_SEPARATOR . 'BankAccount.php_branch.html');
        $path   = file_get_contents(TEST_FILES_PATH . 'tmp' . DIRECTORY_SEPARATOR . 'BankAccount.php_path.html');

        $this->assertNotFalse($branch);
        $this->assertNotFalse($path);

        // decision point on line 13 has one covered and one uncovered outcome
        $this->assertStringContainsString('<span class="branch-hit">&bull;</span><span class="branch-miss">&bull;</span>', $branch);

        // line 14 is included in a covered and in an uncovered branch
        $this->assertStringContainsString('warning popin', $branch);
        $this->assertStringContainsString('1 test covers line 14', $branch);

        // 3 out of 4 branches of setBalance are covered
        $this->assertStringContainsString('<span class="warning">3/4</span>', $branch);

        // line 13 is included in a covered and in an uncovered path
        $this->assertStringContainsString('warning popin', $path);
        $this->assertStringContainsString('1 test covers line 13', $path);

        // 1 out of 2 paths of setBalance is covered
        $this->assertStringContainsString('<span class="warning">1/2</span>', $path);

        // functions without branch and path data are omitted from the structure sections
        $this->assertStringNotContainsString('declarationOnly', $branch);
        $this->assertStringNotContainsString('declarationOnly', $path);

        // the paths of a function with more than 100 paths are collapsed
        $this->assertStringContainsString('<details><summary>101 paths &mdash; click to expand</summary>', $path);
        $this->assertStringContainsString('</details>', $path);
    }

    public function testPathCoverageForBankAccountTest(): void
    {
        $expectedFilesPath = TEST_FILES_PATH . 'Report' . DIRECTORY_SEPARATOR . 'HTML' . DIRECTORY_SEPARATOR . 'PathCoverageForBankAccount';

        $report = new Facade;
        $report->process($this->getPathCoverageForBankAccount()->getReport(), TEST_FILES_PATH . 'tmp');

        $this->assertFilesEquals($expectedFilesPath, TEST_FILES_PATH . 'tmp');
    }

    public function testPathCoverageForSourceWithoutNamespace(): void
    {
        $expectedFilesPath = TEST_FILES_PATH . 'Report' . DIRECTORY_SEPARATOR . 'HTML' . DIRECTORY_SEPARATOR . 'PathCoverageForSourceWithoutNamespace';

        $report = new Facade;
        $report->process($this->getPathCoverageForSourceWithoutNamespace()->getReport(), TEST_FILES_PATH . 'tmp');

        $this->assertFilesEquals($expectedFilesPath, TEST_FILES_PATH . 'tmp');
    }

    public function testForFileWithIgnoredLines(): void
    {
        $expectedFilesPath = TEST_FILES_PATH . 'Report' . DIRECTORY_SEPARATOR . 'HTML' . DIRECTORY_SEPARATOR . 'CoverageForFileWithIgnoredLines';

        $report = new Facade;
        $report->process($this->getCoverageForFileWithIgnoredLines()->getReport(), TEST_FILES_PATH . 'tmp');

        $this->assertFilesEquals($expectedFilesPath, TEST_FILES_PATH . 'tmp');
    }

    public function testForClassWithAnonymousFunction(): void
    {
        $expectedFilesPath = TEST_FILES_PATH . 'Report' . DIRECTORY_SEPARATOR . 'HTML' . DIRECTORY_SEPARATOR . 'CoverageForClassWithAnonymousFunction';

        $report = new Facade;
        $report->process($this->getCoverageForClassWithAnonymousFunction()->getReport(), TEST_FILES_PATH . 'tmp');

        $this->assertFilesEquals($expectedFilesPath, TEST_FILES_PATH . 'tmp');
    }

    public function testHtmlSpecialCharactersInFileAndDirectoryNamesAreEncoded(): void
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            $this->markTestSkipped('The filesystem on Windows does not allow "<", ">" or \'"\' in file or directory names');
        }

        $source = TEST_FILES_PATH . 'tmp' . DIRECTORY_SEPARATOR . 'encoding-source';
        $target = TEST_FILES_PATH . 'tmp' . DIRECTORY_SEPARATOR . 'encoding-report';
        $file   = $source . DIRECTORY_SEPARATOR . 'Resource <X & Y>.php';
        $nested = $source . DIRECTORY_SEPARATOR . 'Group <A & B>' . DIRECTORY_SEPARATOR . 'inner.php';

        mkdir($source . DIRECTORY_SEPARATOR . 'Group <A & B>', 0o777, true);
        file_put_contents($file, "<?php function f() { return 1; }\n");
        file_put_contents($nested, "<?php function g() { return 2; }\n");

        $analyser  = new FileAnalyser(new ParsingSourceAnalyser, false, false);
        $processed = new ProcessedCodeCoverageData;

        foreach ([$file, $nested] as $path) {
            $processed->initializeUnseenData(RawCodeCoverageData::fromUncoveredFile($path, $analyser));
        }

        (new Facade)->process((new Builder($analyser))->build($processed, [], ''), $target);

        $index = file_get_contents($target . DIRECTORY_SEPARATOR . 'index.html');

        $this->assertNotFalse($index);
        $this->assertStringContainsString('Resource &lt;X &amp; Y&gt;.php', $index);
        $this->assertStringContainsString('Group &lt;A &amp; B&gt;', $index);
        $this->assertStringNotContainsString('Resource <X & Y>.php', $index);
        $this->assertStringNotContainsString('Group <A & B>', $index);
    }

    private function assertFilesEquals(string $expectedFilesPath, string $actualFilesPath): void
    {
        $expectedFilesIterator = new FilesystemIterator($expectedFilesPath);
        $actualFilesIterator   = new RegexIterator(new FilesystemIterator($actualFilesPath), '/.html/');

        $this->assertSame(
            iterator_count($expectedFilesIterator),
            iterator_count($actualFilesIterator),
            'Generated files and expected files not match',
        );

        foreach ($expectedFilesIterator as $fileInfo) {
            if (!$fileInfo instanceof SplFileInfo) {
                continue; // @codeCoverageIgnore
            }

            $filename = $fileInfo->getFilename();

            $actualFile = $actualFilesPath . DIRECTORY_SEPARATOR . $filename;

            $this->assertFileExists($actualFile);

            $actual = file_get_contents($actualFile);

            $this->assertNotFalse($actual);

            $this->assertStringMatchesFormatFile(
                $fileInfo->getPathname(),
                str_replace(PHP_EOL, "\n", $actual),
                "{$filename} not match",
            );
        }
    }
}
