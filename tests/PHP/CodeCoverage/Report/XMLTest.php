<?php
/*
 * This file is part of the PHP_CodeCoverage package.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once dirname(dirname(dirname(dirname(__FILE__)))) . DIRECTORY_SEPARATOR . 'TestCase.php';

/**
 * Tests for the PHP_CodeCoverage_Report_XML class.
 *
 * @since Class available since Release 3.1.0
 */
class PHP_CodeCoverage_Report_XMLTest extends PHP_CodeCoverage_TestCase
{
    static private $TEST_REPORT_PATH_SOURCE;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        self::$TEST_REPORT_PATH_SOURCE = TEST_FILES_PATH . 'Report' . DIRECTORY_SEPARATOR . 'XML';
    }

    protected function tearDown()
    {
        parent::tearDown();

        $tmpFilesIterator = new FilesystemIterator(self::$TEST_TMP_PATH);
        foreach ($tmpFilesIterator as $path => $fileInfo) {
            /* @var SplFileInfo $fileInfo */
            unlink($fileInfo->getPathname());
        }
    }

    /**
     * @covers PHP_CodeCoverage_Report_XML
     */
    public function testForBankAccountTest()
    {
        $expectedFilesPath = self::$TEST_REPORT_PATH_SOURCE . DIRECTORY_SEPARATOR . 'CoverageForBankAccount';

        $xml = new PHP_CodeCoverage_Report_XML;
        $xml->process($this->getCoverageForBankAccount(), self::$TEST_TMP_PATH);

        $this->assertFilesEquals($expectedFilesPath, self::$TEST_TMP_PATH);
    }

    /**
     * @covers PHP_CodeCoverage_Report_XML
     */
    public function testForFileWithIgnoredLines()
    {
        $expectedFilesPath = self::$TEST_REPORT_PATH_SOURCE . DIRECTORY_SEPARATOR . 'CoverageForFileWithIgnoredLines';

        $xml = new PHP_CodeCoverage_Report_XML;
        $xml->process($this->getCoverageForFileWithIgnoredLines(), self::$TEST_TMP_PATH);

        $this->assertFilesEquals($expectedFilesPath, self::$TEST_TMP_PATH);
    }

    /**
     * @covers PHP_CodeCoverage_Report_XML
     */
    public function testForClassWithAnonymousFunction()
    {
        $expectedFilesPath =
            self::$TEST_REPORT_PATH_SOURCE . DIRECTORY_SEPARATOR . 'CoverageForClassWithAnonymousFunction';

        $xml = new PHP_CodeCoverage_Report_XML;
        $xml->process($this->getCoverageForClassWithAnonymousFunction(), self::$TEST_TMP_PATH);

        $this->assertFilesEquals($expectedFilesPath, self::$TEST_TMP_PATH);
    }

    /**
     * @param string $expectedFilesPath
     * @param string $actualFilesPath
     */
    protected function assertFilesEquals($expectedFilesPath, $actualFilesPath)
    {
        $expectedFilesIterator = new FilesystemIterator($expectedFilesPath);
        $actualFilesIterator   = new FilesystemIterator($actualFilesPath);

        $this->assertEquals(
            iterator_count($expectedFilesIterator),
            iterator_count($actualFilesIterator),
            'Generated files and expected files not match'
        );
        foreach ($expectedFilesIterator as $path => $fileInfo) {
            /* @var SplFileInfo $fileInfo */
            $filename = $fileInfo->getFilename();

            $actualFile = $actualFilesPath . DIRECTORY_SEPARATOR . $filename;

            $this->assertFileExists($actualFile);
            $this->assertStringMatchesFormatFile(
                $fileInfo->getPathname(),
                file_get_contents($actualFile),
                "${filename} not match"
            );
        }
    }
}
