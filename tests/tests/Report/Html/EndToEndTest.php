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
use const ENT_HTML5;
use const ENT_QUOTES;
use const PHP_EOL;
use function array_keys;
use function dirname;
use function explode;
use function file_get_contents;
use function file_put_contents;
use function html_entity_decode;
use function htmlspecialchars;
use function ksort;
use function mkdir;
use function preg_match_all;
use function sprintf;
use function str_contains;
use function str_replace;
use function str_starts_with;
use function strlen;
use function strpos;
use function substr;
use function substr_count;
use FilesystemIterator;
use PHPUnit\Framework\Attributes\CoversNamespace;
use PHPUnit\Framework\Attributes\Medium;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
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

    public function testLineCoverageWithTestSizesForBankAccountTest(): void
    {
        $expectedFilesPath = TEST_FILES_PATH . 'Report' . DIRECTORY_SEPARATOR . 'HTML' . DIRECTORY_SEPARATOR . 'CoverageForBankAccountWithTestSizes';

        $report = new Facade;
        $report->process($this->coverageForBankAccountWithVariousTestSizesAndStatuses()->getReport(), TEST_FILES_PATH . 'tmp');

        $this->assertFilesEquals($expectedFilesPath, TEST_FILES_PATH . 'tmp');

        $source = file_get_contents(TEST_FILES_PATH . 'tmp' . DIRECTORY_SEPARATOR . 'BankAccount.php.html');

        $this->assertNotFalse($source);

        $getBalanceRowCoverageData = '{"linesTotal":1,"linesAll":1,"methodsTotal":1,"methodsAll":1,' .
            '"linesSmall":0,"methodsSmall":0,"linesMedium":0,"methodsMedium":0,' .
            '"linesLarge":1,"methodsLarge":1,"linesSM":0,"methodsSM":0,' .
            '"linesSL":1,"methodsSL":1,"linesML":1,"methodsML":1,"linesSML":1,"methodsSML":1}';

        $this->assertStringContainsString(
            htmlspecialchars($getBalanceRowCoverageData, ENT_COMPAT),
            $source,
        );

        $classIndex = file_get_contents(TEST_FILES_PATH . 'tmp' . DIRECTORY_SEPARATOR . '_classes' . DIRECTORY_SEPARATOR . 'index.html');
        $classView  = file_get_contents(TEST_FILES_PATH . 'tmp' . DIRECTORY_SEPARATOR . '_classes' . DIRECTORY_SEPARATOR . 'BankAccount.html');

        $this->assertNotFalse($classIndex);
        $this->assertNotFalse($classView);

        $bankAccountCoverageData = htmlspecialchars(
            '{"linesTotal":8,"linesAll":8,"methodsTotal":4,"methodsAll":4,"classesTotal":1,"classesAll":1,' .
            '"linesSmall":4,"methodsSmall":1,"classesSmall":0,"linesMedium":1,"methodsMedium":0,"classesMedium":0,' .
            '"linesLarge":5,"methodsLarge":3,"classesLarge":0,"linesSM":5,"methodsSM":1,"classesSM":0,' .
            '"linesSL":8,"methodsSL":4,"classesSL":1,"linesML":5,"methodsML":3,"classesML":0,' .
            '"linesSML":8,"methodsSML":4,"classesSML":1}',
            ENT_COMPAT,
        );

        $this->assertStringContainsString($bankAccountCoverageData, $classIndex);
        $this->assertStringContainsString($bankAccountCoverageData, $classView);
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

        // the structure sections are sorted by line number, not alphabetically:
        // setBalance() begins on line 11, complex() on line 20
        foreach ([$branch, $path] as $view) {
            $setBalancePosition = strpos($view, '<a name="BankAccount-&gt;setBalance">');
            $complexPosition    = strpos($view, '<a name="BankAccount-&gt;complex">');

            $this->assertNotFalse($setBalancePosition);
            $this->assertNotFalse($complexPosition);
            $this->assertLessThan($complexPosition, $setBalancePosition);
        }

        // the paths of a function with more than 100 paths are collapsed and capped
        $this->assertStringContainsString('<details><summary>101 paths &mdash; click to expand</summary>', $path);
        $this->assertStringContainsString('Only the first 100 paths are shown', $path);
        $this->assertStringContainsString('</details>', $path);
        $this->assertStringContainsString('data-path-index="99"', $path);
        $this->assertStringNotContainsString('data-path-index="100"', $path);

        // the graph highlighting data is capped as well: one "0-exit" edge for each of the 100 rendered paths
        $this->assertSame(100, substr_count($path, '&quot;0-exit&quot;'));
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

    public function testForClassesWithTraitsAndInheritance(): void
    {
        $expectedFilesPath = TEST_FILES_PATH . 'Report' . DIRECTORY_SEPARATOR . 'HTML' . DIRECTORY_SEPARATOR . 'CoverageForClassesWithTraitsAndInheritance';

        $report = new Facade;
        $report->process($this->getCoverageForClassesWithTraitsAndInheritance()->getReport(), TEST_FILES_PATH . 'tmp');

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
        $expectedFiles = $this->htmlFiles($expectedFilesPath);
        $actualFiles   = $this->htmlFiles($actualFilesPath);

        $this->assertSame(
            array_keys($expectedFiles),
            array_keys($actualFiles),
            'Generated files and expected files do not match',
        );

        foreach ($expectedFiles as $relativePath => $expectedFile) {
            $actual = file_get_contents($actualFilesPath . DIRECTORY_SEPARATOR . $relativePath);

            $this->assertNotFalse($actual);

            $this->assertStringMatchesFormatFile(
                $expectedFile,
                str_replace(PHP_EOL, "\n", $actual),
                "{$relativePath} does not match",
            );
        }

        $this->assertRelativeLinkTargetsExist($actualFilesPath);
    }

    private function assertRelativeLinkTargetsExist(string $reportPath): void
    {
        foreach ($this->htmlFiles($reportPath) as $relativePath => $absolutePath) {
            $html = file_get_contents($absolutePath);

            $this->assertNotFalse($html);

            preg_match_all('/(?:href|src)="([^"]+)"/', $html, $matches);

            foreach ($matches[1] as $target) {
                $target = html_entity_decode($target, ENT_QUOTES | ENT_HTML5, 'UTF-8');

                if (str_starts_with($target, '#') || str_contains($target, '://')) {
                    continue;
                }

                $target = explode('#', $target, 2)[0];
                $target = explode('?', $target, 2)[0];

                if ($target === '') {
                    continue;
                }

                $this->assertFileExists(
                    dirname($reportPath . DIRECTORY_SEPARATOR . $relativePath) . DIRECTORY_SEPARATOR . $target,
                    sprintf('%s links to %s which does not exist', $relativePath, $target),
                );
            }
        }
    }

    /**
     * @return array<string, string> Relative path mapped to absolute path, sorted by relative path
     */
    private function htmlFiles(string $basePath): array
    {
        $iterator = new RegexIterator(
            new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($basePath, FilesystemIterator::SKIP_DOTS),
            ),
            '/\.html$/',
        );

        $files = [];

        foreach ($iterator as $fileInfo) {
            if (!$fileInfo instanceof SplFileInfo) {
                continue; // @codeCoverageIgnore
            }

            $files[substr($fileInfo->getPathname(), strlen($basePath) + 1)] = $fileInfo->getPathname();
        }

        ksort($files);

        return $files;
    }
}
