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
use const PHP_INT_MAX;
use function array_keys;
use function dirname;
use function explode;
use function file_get_contents;
use function file_put_contents;
use function html_entity_decode;
use function htmlspecialchars;
use function is_dir;
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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Medium;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Data\ProcessedCodeCoverageData;
use SebastianBergmann\CodeCoverage\Data\RawCodeCoverageData;
use SebastianBergmann\CodeCoverage\FakeDriver;
use SebastianBergmann\CodeCoverage\Filter;
use SebastianBergmann\CodeCoverage\Node\Builder;
use SebastianBergmann\CodeCoverage\StaticAnalysis\FileAnalyser;
use SebastianBergmann\CodeCoverage\StaticAnalysis\ParsingSourceAnalyser;
use SebastianBergmann\CodeCoverage\TestCase;
use SplFileInfo;

#[CoversNamespace('SebastianBergmann\CodeCoverage\Report\Html')]
#[Medium]
final class EndToEndTest extends TestCase
{
    /**
     * @return non-empty-list<array{0: positive-int, 1: non-empty-string, 2: non-empty-string}>
     */
    public static function hitCounts(): array
    {
        return [
            [2, '2', '2'],
            [999, '999', '999'],
            [1000, '1.00k', '1,000'],
            [1234, '1.23k', '1,234'],
            [1239, '1.23k', '1,239'],
            [12345, '12.3k', '12,345'],
            [123456, '123k', '123,456'],
            [999999, '999k', '999,999'],
            [1000000, '1.00M', '1,000,000'],
            [1050000, '1.05M', '1,050,000'],
            [2500000000, '2.50G', '2,500,000,000'],
            [PHP_INT_MAX, '9.22E', '9,223,372,036,854,775,807'],
        ];
    }

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

    public function testHitCountsAreShownInGutterWhenDriverCollectsThem(): void
    {
        $report = new Facade;
        $report->process($this->getLineCoverageWithHitCountsForBankAccount()->getReport(), TEST_FILES_PATH . 'tmp');

        $source = file_get_contents(TEST_FILES_PATH . 'tmp' . DIRECTORY_SEPARATOR . 'BankAccount.php.html');

        $this->assertNotFalse($source);

        $this->assertStringContainsString('<table class="source with-hit-counts">', $source);

        // line 8 was executed twice by testBalanceIsInitiallyZero and three times by testDepositWithdrawMoney
        $this->assertStringContainsString('<td class="hit-count">5</td>', $source);
        $this->assertStringContainsString('data-popover-title="2 tests cover line 8. It was executed 5 times."', $source);

        // line 22 was executed once by testBalanceCannotBecomeNegative2 and twice by testDepositWithdrawMoney
        $this->assertStringContainsString('<td class="hit-count">3</td>', $source);

        // line 31 was executed once by testDepositWithdrawMoney
        $this->assertStringContainsString('data-popover-title="1 test covers line 31. It was executed once."', $source);

        // uncovered and non-executable lines do not get a hit count
        $this->assertStringContainsString('<td class="hit-count"></td>', $source);

        $this->assertStringContainsString('<li class="note">Number next to a line: how often the line was executed</li>', $source);

        $classView = file_get_contents(TEST_FILES_PATH . 'tmp' . DIRECTORY_SEPARATOR . '_classes' . DIRECTORY_SEPARATOR . 'BankAccount.html');

        $this->assertNotFalse($classView);
        $this->assertStringContainsString('<table class="source with-hit-counts">', $classView);
        $this->assertStringContainsString('<td class="hit-count">5</td>', $classView);
        $this->assertStringContainsString('<td class="hit-count">3</td>', $classView);
    }

    public function testNumberOfTestsThatCoverALineIsShownInGutterWhenDriverDoesNotCollectHitCounts(): void
    {
        $report = new Facade;
        $report->process($this->getLineCoverageForBankAccount()->getReport(), TEST_FILES_PATH . 'tmp');

        $source    = file_get_contents(TEST_FILES_PATH . 'tmp' . DIRECTORY_SEPARATOR . 'BankAccount.php.html');
        $classView = file_get_contents(TEST_FILES_PATH . 'tmp' . DIRECTORY_SEPARATOR . '_classes' . DIRECTORY_SEPARATOR . 'BankAccount.html');

        $this->assertNotFalse($source);
        $this->assertNotFalse($classView);

        foreach ([$source, $classView] as $html) {
            $this->assertStringContainsString('<table class="source with-hit-counts">', $html);

            // line 8 is covered by testBalanceIsInitiallyZero and testDepositWithdrawMoney
            $this->assertStringContainsString('data-popover-title="2 tests cover line 8"', $html);
            $this->assertStringContainsString('<a id="8" href="#8">8</a></td><td class="hit-count">2</td>', $html);

            // line 24 is covered by testDepositWithdrawMoney
            $this->assertStringContainsString('data-popover-title="1 test covers line 24"', $html);
            $this->assertStringContainsString('<a id="24" href="#24">24</a></td><td class="hit-count">1</td>', $html);

            // line 16 is not covered, line 9 is not executable
            $this->assertStringContainsString('<a id="16" href="#16">16</a></td><td class="hit-count"></td>', $html);
            $this->assertStringContainsString('<a id="9" href="#9">9</a></td><td class="hit-count"></td>', $html);

            $this->assertStringContainsString('<li class="note">Number next to a line: tests that cover the line</li>', $html);
        }
    }

    #[DataProvider('hitCounts')]
    public function testHitCountsOfOneThousandAndMoreAreAbbreviated(int $hitCount, string $abbreviated, string $exact): void
    {
        $file = TEST_FILES_PATH . 'BankAccount.php';

        $driver = new FakeDriver(RawCodeCoverageData::fromLineCoverage([$file => [8 => $hitCount]]));

        $driver->markAsCollectingHitCounts();

        $filter = new Filter;
        $filter->includeFile($file);

        $coverage = new CodeCoverage($driver, $filter);

        $coverage->start('A test');
        $coverage->stop();

        (new Facade)->process($coverage->getReport(), TEST_FILES_PATH . 'tmp');

        $source = file_get_contents(TEST_FILES_PATH . 'tmp' . DIRECTORY_SEPARATOR . 'BankAccount.php.html');

        $this->assertNotFalse($source);
        $this->assertStringContainsString('<a id="8" href="#8">8</a></td><td class="hit-count">' . $abbreviated . '</td>', $source);
        $this->assertStringContainsString('data-popover-title="1 test covers line 8. It was executed ' . $exact . ' times."', $source);
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

    public function testTestSizeFilterDisablesTestSizesThatHaveNoCoverageData(): void
    {
        $report = new Facade;
        $report->process($this->lineCoverageForBankAccountWithoutMediumTests()->getReport(), TEST_FILES_PATH . 'tmp');

        foreach (['index.html', 'BankAccount.php.html', '_classes' . DIRECTORY_SEPARATOR . 'index.html'] as $file) {
            $contents = file_get_contents(TEST_FILES_PATH . 'tmp' . DIRECTORY_SEPARATOR . $file);

            $this->assertNotFalse($contents);

            $this->assertStringContainsString('data-test-size-filter="small" autocomplete="off">', $contents, $file);
            $this->assertStringContainsString('data-test-size-filter="large" autocomplete="off">', $contents, $file);

            // no test of size medium covered any code, so filtering by that
            // test size could only ever result in 0%
            $this->assertStringContainsString('data-test-size-filter="medium" autocomplete="off" disabled>', $contents, $file);
            $this->assertStringContainsString('title="No code in this report is covered by tests of size medium"', $contents, $file);
        }
    }

    public function testTestSizeFilterIsRenderedWhenOnlyOneTestSizeHasCoverageData(): void
    {
        $report = new Facade;
        $report->process($this->lineCoverageForBankAccountWithSmallTestsOnly()->getReport(), TEST_FILES_PATH . 'tmp');

        foreach (['index.html', 'BankAccount.php.html', '_classes' . DIRECTORY_SEPARATOR . 'index.html'] as $file) {
            $contents = file_get_contents(TEST_FILES_PATH . 'tmp' . DIRECTORY_SEPARATOR . $file);

            $this->assertNotFalse($contents);

            $this->assertStringContainsString('data-test-size-filter="small" autocomplete="off">', $contents, $file);
            $this->assertStringContainsString('data-test-size-filter="medium" autocomplete="off" disabled>', $contents, $file);
            $this->assertStringContainsString('data-test-size-filter="large" autocomplete="off" disabled>', $contents, $file);
        }
    }

    public function testTestSizeFilterIsNotRenderedWhenCoverageDataHasNoTestSizes(): void
    {
        $report = new Facade;
        $report->process($this->getLineCoverageForBankAccount()->getReport(), TEST_FILES_PATH . 'tmp');

        $index = file_get_contents(TEST_FILES_PATH . 'tmp' . DIRECTORY_SEPARATOR . 'index.html');

        $this->assertNotFalse($index);
        $this->assertStringNotContainsString('test-size-filter', $index);
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
        $this->assertStringContainsString('<span class="badge warning">3/4</span>', $branch);

        // line 13 is included in a covered and in an uncovered path
        $this->assertStringContainsString('warning popin', $path);
        $this->assertStringContainsString('1 test covers line 13', $path);

        // 1 out of 2 paths of setBalance is covered
        $this->assertStringContainsString('<span class="badge warning">1/2</span>', $path);

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

    public function testDirectoryThatOnlyContainsAnotherDirectoryOrASingleFileIsCollapsed(): void
    {
        $source = TEST_FILES_PATH . 'tmp' . DIRECTORY_SEPARATOR . 'collapsing-source';
        $target = TEST_FILES_PATH . 'tmp' . DIRECTORY_SEPARATOR . 'collapsing-report';

        $files = [
            $source . DIRECTORY_SEPARATOR . 'root.php',
            $source . DIRECTORY_SEPARATOR . 'a' . DIRECTORY_SEPARATOR . 'b' . DIRECTORY_SEPARATOR . 'c' . DIRECTORY_SEPARATOR . 'x.php',
            $source . DIRECTORY_SEPARATOR . 'a' . DIRECTORY_SEPARATOR . 'b' . DIRECTORY_SEPARATOR . 'c' . DIRECTORY_SEPARATOR . 'y.php',
            $source . DIRECTORY_SEPARATOR . 'd' . DIRECTORY_SEPARATOR . 'e' . DIRECTORY_SEPARATOR . 'only.php',
            $source . DIRECTORY_SEPARATOR . 'f' . DIRECTORY_SEPARATOR . 'one.php',
            $source . DIRECTORY_SEPARATOR . 'f' . DIRECTORY_SEPARATOR . 'two.php',
        ];

        $analyser  = new FileAnalyser(new ParsingSourceAnalyser, false, false);
        $processed = new ProcessedCodeCoverageData;

        foreach ($files as $index => $file) {
            if (!is_dir(dirname($file))) {
                mkdir(dirname($file), 0o777, true);
            }

            file_put_contents($file, sprintf("<?php function f%d() { return 1; }\n", $index));

            $processed->initializeUnseenData(RawCodeCoverageData::fromUncoveredFile($file, $analyser));
        }

        (new Facade)->process((new Builder($analyser))->build($processed, [], ''), $target);

        $index = file_get_contents($target . DIRECTORY_SEPARATOR . 'index.html');

        $this->assertNotFalse($index);

        // a and a/b contain nothing but another directory
        $this->assertStringContainsString('<span class="icon icon-directory" aria-hidden="true"></span><a href="a/b/c/index.html">a/b/c</a>', $index);

        // d contains nothing but e, which contains nothing but a single file
        $this->assertStringContainsString('<span class="icon icon-file" aria-hidden="true"></span><a href="d/e/only.php.html">d/e/only.php</a>', $index);

        // f contains two files
        $this->assertStringContainsString('<a href="f/index.html">f</a>', $index);
        $this->assertStringContainsString('<a href="root.php.html">root.php</a>', $index);

        $this->assertStringNotContainsString('<a href="a/index.html">', $index);
        $this->assertStringNotContainsString('<a href="d/index.html">', $index);

        // the pages of the directories that are skipped are still generated
        $this->assertFileExists($target . DIRECTORY_SEPARATOR . 'a' . DIRECTORY_SEPARATOR . 'index.html');
        $this->assertFileExists($target . DIRECTORY_SEPARATOR . 'a' . DIRECTORY_SEPARATOR . 'b' . DIRECTORY_SEPARATOR . 'index.html');
        $this->assertFileExists($target . DIRECTORY_SEPARATOR . 'd' . DIRECTORY_SEPARATOR . 'e' . DIRECTORY_SEPARATOR . 'index.html');

        $nested = file_get_contents($target . DIRECTORY_SEPARATOR . 'a' . DIRECTORY_SEPARATOR . 'index.html');

        $this->assertNotFalse($nested);
        $this->assertStringContainsString('<a href="b/c/index.html">b/c</a>', $nested);

        $this->assertRelativeLinkTargetsExist($target);
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
