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
use function iterator_count;
use function str_replace;
use FilesystemIterator;
use RegexIterator;
use SebastianBergmann\CodeCoverage\TestCase;

final class HtmlTest extends TestCase
{
    private static $TEST_REPORT_PATH_SOURCE;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$TEST_REPORT_PATH_SOURCE = TEST_FILES_PATH . 'Report' . DIRECTORY_SEPARATOR . 'HTML';
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->removeTemporaryFiles();
    }

    public function testLineCoverageForBankAccountTest(): void
    {
        $expectedFilesPath = self::$TEST_REPORT_PATH_SOURCE . DIRECTORY_SEPARATOR . 'CoverageForBankAccount';

        $report = new Facade;
        $report->process($this->getLineCoverageForBankAccount(), self::$TEST_TMP_PATH);

        $this->assertFilesEquals($expectedFilesPath, self::$TEST_TMP_PATH);
    }

    public function testPathCoverageForBankAccountTest(): void
    {
        $expectedFilesPath = self::$TEST_REPORT_PATH_SOURCE . DIRECTORY_SEPARATOR . 'PathCoverageForBankAccount';

        $report = new Facade;
        $report->process($this->getPathCoverageForBankAccount(), self::$TEST_TMP_PATH);

        // Prevent error: "preg_match(): Compilation failed: regular expression is too large at offset 35557"
        $expectedFilesPath2 = self::$TEST_TMP_PATH . '2';
        if (!file_exists($expectedFilesPath2)){
            mkdir($expectedFilesPath2);
        }
        $paths = glob("{$expectedFilesPath2}/*");
        foreach ($paths as $path) {
            unlink($path);
        }
        
        $paths = glob("{$expectedFilesPath}/*");
        foreach ($paths as $path) {
            $file = basename($path);
            copy($path, "{$expectedFilesPath2}/{$file}");
        }
        
        foreach (
          [
            'BankAccount.php_branch.html',
            'BankAccount.php_path.html',
          ] as $file) {
            foreach ([$expectedFilesPath2, self::$TEST_TMP_PATH] as $dir) {
                $path = "{$dir}/{$file}";
                $lines = file($path);
                $half = (int)(count($lines) / 2);
                file_put_contents("{$path}.part1", join('', array_slice($lines, 0, $half)));
                file_put_contents("{$path}.part2", join('', array_slice($lines, $half)));
                unlink($path);
            }
        }
        $this->assertFilesEquals($expectedFilesPath2, self::$TEST_TMP_PATH);

        $paths = glob("{$expectedFilesPath2}/*");
        foreach ($paths as $path) {
            unlink($path);
        }
        rmdir($expectedFilesPath2);
    }

    public function testPathCoverageForSourceWithoutNamespace(): void
    {
        $expectedFilesPath = self::$TEST_REPORT_PATH_SOURCE . DIRECTORY_SEPARATOR . 'PathCoverageForSourceWithoutNamespace';

        $report = new Facade;
        $report->process($this->getPathCoverageForSourceWithoutNamespace(), self::$TEST_TMP_PATH);

        $this->assertFilesEquals($expectedFilesPath, self::$TEST_TMP_PATH);
    }

    public function testForFileWithIgnoredLines(): void
    {
        $expectedFilesPath = self::$TEST_REPORT_PATH_SOURCE . DIRECTORY_SEPARATOR . 'CoverageForFileWithIgnoredLines';

        $report = new Facade;
        $report->process($this->getCoverageForFileWithIgnoredLines(), self::$TEST_TMP_PATH);

        $this->assertFilesEquals($expectedFilesPath, self::$TEST_TMP_PATH);
    }

    public function testForClassWithAnonymousFunction(): void
    {
        $expectedFilesPath = self::$TEST_REPORT_PATH_SOURCE . DIRECTORY_SEPARATOR . 'CoverageForClassWithAnonymousFunction';

        $report = new Facade;
        $report->process($this->getCoverageForClassWithAnonymousFunction(), self::$TEST_TMP_PATH);

        $this->assertFilesEquals($expectedFilesPath, self::$TEST_TMP_PATH);
    }

    private function assertFilesEquals(string $expectedFilesPath, string $actualFilesPath): void
    {
        $expectedFilesIterator = new FilesystemIterator($expectedFilesPath);
        $actualFilesIterator   = new RegexIterator(new FilesystemIterator($actualFilesPath), '/.html/');

        $this->assertEquals(
            iterator_count($expectedFilesIterator),
            iterator_count($actualFilesIterator),
            'Generated files and expected files not match'
        );

        foreach ($expectedFilesIterator as $path => $fileInfo) {
            /* @var \SplFileInfo $fileInfo */
            $filename = $fileInfo->getFilename();

            $actualFile = $actualFilesPath . DIRECTORY_SEPARATOR . $filename;

            $this->assertFileExists($actualFile);

            $this->assertStringMatchesFormatFile(
                $fileInfo->getPathname(),
                str_replace(PHP_EOL, "\n", file_get_contents($actualFile)),
                "{$filename} not match"
            );
        }
    }
}
