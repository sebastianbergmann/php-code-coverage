<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage;

use function sort;
use function unserialize;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\FileIterator\Facade as FileIteratorFacade;

/**
 * @covers \SebastianBergmann\CodeCoverage\Filter
 */
final class FilterTest extends TestCase
{
    /**
     * @var Filter
     */
    private $filter;

    /**
     * @var array
     */
    private $files = [];

    protected function setUp(): void
    {
        $this->filter = unserialize('O:37:"SebastianBergmann\CodeCoverage\Filter":0:{}');

        $this->files = [
            TEST_FILES_PATH . 'BankAccount.php',
            TEST_FILES_PATH . 'BankAccountTest.php',
            TEST_FILES_PATH . 'CoverageClassExtendedTest.php',
            TEST_FILES_PATH . 'CoverageClassTest.php',
            TEST_FILES_PATH . 'CoverageFunctionParenthesesTest.php',
            TEST_FILES_PATH . 'CoverageFunctionParenthesesWhitespaceTest.php',
            TEST_FILES_PATH . 'CoverageFunctionTest.php',
            TEST_FILES_PATH . 'CoverageMethodOneLineAnnotationTest.php',
            TEST_FILES_PATH . 'CoverageMethodParenthesesTest.php',
            TEST_FILES_PATH . 'CoverageMethodParenthesesWhitespaceTest.php',
            TEST_FILES_PATH . 'CoverageMethodTest.php',
            TEST_FILES_PATH . 'CoverageNoneTest.php',
            TEST_FILES_PATH . 'CoverageNotPrivateTest.php',
            TEST_FILES_PATH . 'CoverageNotProtectedTest.php',
            TEST_FILES_PATH . 'CoverageNotPublicTest.php',
            TEST_FILES_PATH . 'CoverageNothingTest.php',
            TEST_FILES_PATH . 'CoveragePrivateTest.php',
            TEST_FILES_PATH . 'CoverageProtectedTest.php',
            TEST_FILES_PATH . 'CoveragePublicTest.php',
            TEST_FILES_PATH . 'CoverageTwoDefaultClassAnnotations.php',
            TEST_FILES_PATH . 'CoveredClass.php',
            TEST_FILES_PATH . 'CoveredFunction.php',
            TEST_FILES_PATH . 'Crash.php',
            TEST_FILES_PATH . 'NamespaceCoverageClassExtendedTest.php',
            TEST_FILES_PATH . 'NamespaceCoverageClassTest.php',
            TEST_FILES_PATH . 'NamespaceCoverageCoversClassPublicTest.php',
            TEST_FILES_PATH . 'NamespaceCoverageCoversClassTest.php',
            TEST_FILES_PATH . 'NamespaceCoverageMethodTest.php',
            TEST_FILES_PATH . 'NamespaceCoverageNotPrivateTest.php',
            TEST_FILES_PATH . 'NamespaceCoverageNotProtectedTest.php',
            TEST_FILES_PATH . 'NamespaceCoverageNotPublicTest.php',
            TEST_FILES_PATH . 'NamespaceCoveragePrivateTest.php',
            TEST_FILES_PATH . 'NamespaceCoverageProtectedTest.php',
            TEST_FILES_PATH . 'NamespaceCoveragePublicTest.php',
            TEST_FILES_PATH . 'NamespaceCoveredClass.php',
            TEST_FILES_PATH . 'NamespacedBankAccount.php',
            TEST_FILES_PATH . 'NotExistingCoveredElementTest.php',
            TEST_FILES_PATH . 'source_with_class_and_anonymous_function.php',
            TEST_FILES_PATH . 'source_with_empty_class.php',
            TEST_FILES_PATH . 'source_with_ignore.php',
            TEST_FILES_PATH . 'source_with_interface.php',
            TEST_FILES_PATH . 'source_with_namespace.php',
            TEST_FILES_PATH . 'source_with_oneline_annotations.php',
            TEST_FILES_PATH . 'source_with_use_statements.php',
            TEST_FILES_PATH . 'source_without_ignore.php',
            TEST_FILES_PATH . 'source_without_namespace.php',
        ];
    }

    public function testAddingAFileToTheWhitelistWorks(): void
    {
        $this->filter->includeFile($this->files[0]);

        $this->assertEquals(
            [$this->files[0]],
            $this->filter->files()
        );
    }

    public function testRemovingAFileFromTheWhitelistWorks(): void
    {
        $this->filter->includeFile($this->files[0]);
        $this->filter->excludeFile($this->files[0]);

        $this->assertEquals([], $this->filter->files());
    }

    /**
     * @depends testAddingAFileToTheWhitelistWorks
     */
    public function testAddingADirectoryToTheWhitelistWorks(): void
    {
        $this->filter->includeDirectory(TEST_FILES_PATH);

        $whitelist = $this->filter->files();
        sort($whitelist);

        $this->assertEquals($this->files, $whitelist);
    }

    public function testAddingFilesToTheWhitelistWorks(): void
    {
        $files = (new FileIteratorFacade)->getFilesAsArray(
            TEST_FILES_PATH,
            $suffixes = '.php'
        );

        $this->filter->includeFiles($files);

        $whitelist = $this->filter->files();
        sort($whitelist);

        $this->assertEquals($this->files, $whitelist);
    }

    /**
     * @depends testAddingADirectoryToTheWhitelistWorks
     */
    public function testRemovingADirectoryFromTheWhitelistWorks(): void
    {
        $this->filter->includeDirectory(TEST_FILES_PATH);
        $this->filter->excludeDirectory(TEST_FILES_PATH);

        $this->assertEquals([], $this->filter->files());
    }

    public function testIsFile(): void
    {
        $this->assertFalse($this->filter->isFile('vfs://root/a/path'));
        $this->assertFalse($this->filter->isFile('xdebug://debug-eval'));
        $this->assertFalse($this->filter->isFile('eval()\'d code'));
        $this->assertFalse($this->filter->isFile('runtime-created function'));
        $this->assertFalse($this->filter->isFile('assert code'));
        $this->assertFalse($this->filter->isFile('regexp code'));
        $this->assertTrue($this->filter->isFile(__FILE__));
    }

    public function testWhitelistedFileIsNotFiltered(): void
    {
        $this->filter->includeFile($this->files[0]);
        $this->assertFalse($this->filter->isExcluded($this->files[0]));
    }

    public function testNotWhitelistedFileIsFiltered(): void
    {
        $this->filter->includeFile($this->files[0]);
        $this->assertTrue($this->filter->isExcluded($this->files[1]));
    }

    public function testNonFilesAreFiltered(): void
    {
        $this->assertTrue($this->filter->isExcluded('vfs://root/a/path'));
        $this->assertTrue($this->filter->isExcluded('xdebug://debug-eval'));
        $this->assertTrue($this->filter->isExcluded('eval()\'d code'));
        $this->assertTrue($this->filter->isExcluded('runtime-created function'));
        $this->assertTrue($this->filter->isExcluded('assert code'));
        $this->assertTrue($this->filter->isExcluded('regexp code'));
    }

    /**
     * @ticket https://github.com/sebastianbergmann/php-code-coverage/issues/664
     */
    public function testTryingToAddFileThatDoesNotExistDoesNotChangeFilter(): void
    {
        $filter = new Filter;

        $filter->includeFile('does_not_exist');

        $this->assertEmpty($filter->files());
    }
}
