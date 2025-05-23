<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\Report\Xml;

use const DIRECTORY_SEPARATOR;
use function file_get_contents;
use function iterator_count;
use function unlink;
use FilesystemIterator;
use PHPUnit\Framework\Attributes\CoversNamespace;
use PHPUnit\Framework\Attributes\Small;
use SebastianBergmann\CodeCoverage\TestCase;

#[CoversNamespace('SebastianBergmann\CodeCoverage\Report\Xml')]
#[Small]
final class XmlTest extends TestCase
{
    private static string $TEST_REPORT_PATH_SOURCE;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$TEST_REPORT_PATH_SOURCE = TEST_FILES_PATH . 'Report' . DIRECTORY_SEPARATOR . 'XML';
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        foreach (new FilesystemIterator(TEST_FILES_PATH . 'tmp') as $path => $fileInfo) {
            /* @var \SplFileInfo $fileInfo */
            unlink($fileInfo->getPathname());
        }
    }

    public function testForBankAccountTest(): void
    {
        $expectedFilesPath = self::$TEST_REPORT_PATH_SOURCE . DIRECTORY_SEPARATOR . 'CoverageForBankAccount';

        $xml = new Facade('1.0.0');
        $xml->process($this->getLineCoverageForBankAccount(), TEST_FILES_PATH . 'tmp');

        $this->assertFilesEquals($expectedFilesPath, TEST_FILES_PATH . 'tmp');
    }

    public function testForFileWithIgnoredLines(): void
    {
        $expectedFilesPath = self::$TEST_REPORT_PATH_SOURCE . DIRECTORY_SEPARATOR . 'CoverageForFileWithIgnoredLines';

        $xml = new Facade('1.0.0');
        $xml->process($this->getCoverageForFileWithIgnoredLines(), TEST_FILES_PATH . 'tmp');

        $this->assertFilesEquals($expectedFilesPath, TEST_FILES_PATH . 'tmp');
    }

    public function testForClassWithAnonymousFunction(): void
    {
        $expectedFilesPath = self::$TEST_REPORT_PATH_SOURCE . DIRECTORY_SEPARATOR . 'CoverageForClassWithAnonymousFunction';

        $xml = new Facade('1.0.0');
        $xml->process($this->getCoverageForClassWithAnonymousFunction(), TEST_FILES_PATH . 'tmp');

        $this->assertFilesEquals($expectedFilesPath, TEST_FILES_PATH . 'tmp');
    }

    private function assertFilesEquals(string $expectedFilesPath, string $actualFilesPath): void
    {
        $expectedFilesIterator = new FilesystemIterator($expectedFilesPath);
        $actualFilesIterator   = new FilesystemIterator($actualFilesPath);

        $this->assertEquals(
            iterator_count($expectedFilesIterator),
            iterator_count($actualFilesIterator),
            'Generated files and expected files not match',
        );

        foreach ($expectedFilesIterator as $path => $fileInfo) {
            /* @var \SplFileInfo $fileInfo */
            $filename = $fileInfo->getFilename();

            $actualFile = $actualFilesPath . DIRECTORY_SEPARATOR . $filename;

            $this->assertFileExists($actualFile);

            $this->assertStringMatchesFormatFile(
                $fileInfo->getPathname(),
                file_get_contents($actualFile),
                "{$filename} not match",
            );
        }
    }
}
