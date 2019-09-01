<?php declare(strict_types=1);
/*
 * This file is part of the php-code-coverage package.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\Report\Xml;

use SebastianBergmann\CodeCoverage\RuntimeException;
use SebastianBergmann\CodeCoverage\TestCase;

class XmlTest extends TestCase
{
    private static $TEST_REPORT_PATH_SOURCE;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$TEST_REPORT_PATH_SOURCE = TEST_FILES_PATH . 'Report' . \DIRECTORY_SEPARATOR . 'XML';
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $tmpFilesIterator = new \FilesystemIterator(self::$TEST_TMP_PATH);

        foreach ($tmpFilesIterator as $path => $fileInfo) {
            /* @var \SplFileInfo $fileInfo */
            if (!\is_dir($fileInfo->getPathname())) {
                \unlink($fileInfo->getPathname());
            } else {
                \rmdir($fileInfo->getPathname());
            }
        }
    }

    public function testForBankAccountTest(): void
    {
        $expectedFilesPath = self::$TEST_REPORT_PATH_SOURCE . \DIRECTORY_SEPARATOR . 'CoverageForBankAccount';

        $xml = new Facade('1.0.0');
        $xml->process($this->getCoverageForBankAccount(), self::$TEST_TMP_PATH);

        $this->assertFilesEquals($expectedFilesPath, self::$TEST_TMP_PATH);
    }

    public function testForFileWithIgnoredLines(): void
    {
        $expectedFilesPath = self::$TEST_REPORT_PATH_SOURCE . \DIRECTORY_SEPARATOR . 'CoverageForFileWithIgnoredLines';

        $xml = new Facade('1.0.0');
        $xml->process($this->getCoverageForFileWithIgnoredLines(), self::$TEST_TMP_PATH);

        $this->assertFilesEquals($expectedFilesPath, self::$TEST_TMP_PATH);
    }

    public function testForClassWithAnonymousFunction(): void
    {
        $expectedFilesPath = self::$TEST_REPORT_PATH_SOURCE . \DIRECTORY_SEPARATOR . 'CoverageForClassWithAnonymousFunction';

        $xml = new Facade('1.0.0');
        $xml->process($this->getCoverageForClassWithAnonymousFunction(), self::$TEST_TMP_PATH);

        $this->assertFilesEquals($expectedFilesPath, self::$TEST_TMP_PATH);
    }

    public function testReportThrowsRuntimeExceptionWhenUnableToCreateTargetDir(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("'/foo/bar/baz/' could not be created.");

        $xml = new Facade('1.0.0');
        $xml->process($this->getCoverageForBankAccount(), '/foo/bar/baz');
    }

    public function testReportThrowsRuntimeExceptionWhenUnableToWriteToTargetDir(): void
    {
        $target = self::$TEST_TMP_PATH . '/non-writable-dir';
        @\mkdir($target, 0555);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("'$target/' exists but is not writable");

        $xml = new Facade('1.0.0');
        $xml->process($this->getCoverageForBankAccount(), $target);
    }

    /**
     * @param string $expectedFilesPath
     * @param string $actualFilesPath
     */
    private function assertFilesEquals($expectedFilesPath, $actualFilesPath): void
    {
        $expectedFilesIterator = new \FilesystemIterator($expectedFilesPath);
        $actualFilesIterator   = new \FilesystemIterator($actualFilesPath);

        $this->assertEquals(
            \iterator_count($expectedFilesIterator),
            \iterator_count($actualFilesIterator),
            'Generated files and expected files not match'
        );

        foreach ($expectedFilesIterator as $path => $fileInfo) {
            /* @var \SplFileInfo $fileInfo */
            $filename = $fileInfo->getFilename();

            $actualFile = $actualFilesPath . \DIRECTORY_SEPARATOR . $filename;

            $this->assertFileExists($actualFile);

            $this->assertStringMatchesFormatFile(
                $fileInfo->getPathname(),
                \file_get_contents($actualFile),
                "${filename} not match"
            );
        }
    }
}
