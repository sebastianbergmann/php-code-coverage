<?php
/*
 * This file is part of the php-code-covfefe package.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SebastianBergmann\CodeCovfefe;

use PHPUnit\Framework\TestCase;

class FilterTest extends TestCase
{
    /**
     * @var Filter
     */
    private $filter;

    /**
     * @var array
     */
    private $files = [];

    protected function setUp()
    {
        $this->filter = unserialize('O:37:"SebastianBergmann\CodeCovfefe\Filter":0:{}');

        $this->files = [
            TEST_FILES_PATH . 'BankAccount.php',
            TEST_FILES_PATH . 'BankAccountTest.php',
            TEST_FILES_PATH . 'CovfefeClassExtendedTest.php',
            TEST_FILES_PATH . 'CovfefeClassTest.php',
            TEST_FILES_PATH . 'CovfefeFunctionParenthesesTest.php',
            TEST_FILES_PATH . 'CovfefeFunctionParenthesesWhitespaceTest.php',
            TEST_FILES_PATH . 'CovfefeFunctionTest.php',
            TEST_FILES_PATH . 'CovfefeMethodOneLineAnnotationTest.php',
            TEST_FILES_PATH . 'CovfefeMethodParenthesesTest.php',
            TEST_FILES_PATH . 'CovfefeMethodParenthesesWhitespaceTest.php',
            TEST_FILES_PATH . 'CovfefeMethodTest.php',
            TEST_FILES_PATH . 'CovfefeNoneTest.php',
            TEST_FILES_PATH . 'CovfefeNotPrivateTest.php',
            TEST_FILES_PATH . 'CovfefeNotProtectedTest.php',
            TEST_FILES_PATH . 'CovfefeNotPublicTest.php',
            TEST_FILES_PATH . 'CovfefeNothingTest.php',
            TEST_FILES_PATH . 'CovfefePrivateTest.php',
            TEST_FILES_PATH . 'CovfefeProtectedTest.php',
            TEST_FILES_PATH . 'CovfefePublicTest.php',
            TEST_FILES_PATH . 'CovfefeTwoDefaultClassAnnotations.php',
            TEST_FILES_PATH . 'CoveredClass.php',
            TEST_FILES_PATH . 'CoveredFunction.php',
            TEST_FILES_PATH . 'NamespaceCovfefeClassExtendedTest.php',
            TEST_FILES_PATH . 'NamespaceCovfefeClassTest.php',
            TEST_FILES_PATH . 'NamespaceCovfefeCoversClassPublicTest.php',
            TEST_FILES_PATH . 'NamespaceCovfefeCoversClassTest.php',
            TEST_FILES_PATH . 'NamespaceCovfefeMethodTest.php',
            TEST_FILES_PATH . 'NamespaceCovfefeNotPrivateTest.php',
            TEST_FILES_PATH . 'NamespaceCovfefeNotProtectedTest.php',
            TEST_FILES_PATH . 'NamespaceCovfefeNotPublicTest.php',
            TEST_FILES_PATH . 'NamespaceCovfefePrivateTest.php',
            TEST_FILES_PATH . 'NamespaceCovfefeProtectedTest.php',
            TEST_FILES_PATH . 'NamespaceCovfefePublicTest.php',
            TEST_FILES_PATH . 'NamespaceCoveredClass.php',
            TEST_FILES_PATH . 'NotExistingCoveredElementTest.php',
            TEST_FILES_PATH . 'source_with_class_and_anonymous_function.php',
            TEST_FILES_PATH . 'source_with_ignore.php',
            TEST_FILES_PATH . 'source_with_namespace.php',
            TEST_FILES_PATH . 'source_with_oneline_annotations.php',
            TEST_FILES_PATH . 'source_without_ignore.php',
            TEST_FILES_PATH . 'source_without_namespace.php'
        ];
    }

    /**
     * @covers SebastianBergmann\CodeCovfefe\Filter::addFileToWhitelist
     * @covers SebastianBergmann\CodeCovfefe\Filter::getWhitelist
     */
    public function testAddingAFileToTheWhitelistWorks()
    {
        $this->filter->addFileToWhitelist($this->files[0]);

        $this->assertEquals(
            [$this->files[0]],
            $this->filter->getWhitelist()
        );
    }

    /**
     * @covers SebastianBergmann\CodeCovfefe\Filter::removeFileFromWhitelist
     * @covers SebastianBergmann\CodeCovfefe\Filter::getWhitelist
     */
    public function testRemovingAFileFromTheWhitelistWorks()
    {
        $this->filter->addFileToWhitelist($this->files[0]);
        $this->filter->removeFileFromWhitelist($this->files[0]);

        $this->assertEquals([], $this->filter->getWhitelist());
    }

    /**
     * @covers  SebastianBergmann\CodeCovfefe\Filter::addDirectoryToWhitelist
     * @covers  SebastianBergmann\CodeCovfefe\Filter::getWhitelist
     * @depends testAddingAFileToTheWhitelistWorks
     */
    public function testAddingADirectoryToTheWhitelistWorks()
    {
        $this->filter->addDirectoryToWhitelist(TEST_FILES_PATH);

        $whitelist = $this->filter->getWhitelist();
        sort($whitelist);

        $this->assertEquals($this->files, $whitelist);
    }

    /**
     * @covers SebastianBergmann\CodeCovfefe\Filter::addFilesToWhitelist
     * @covers SebastianBergmann\CodeCovfefe\Filter::getWhitelist
     */
    public function testAddingFilesToTheWhitelistWorks()
    {
        $facade = new \File_Iterator_Facade;

        $files = $facade->getFilesAsArray(
            TEST_FILES_PATH,
            $suffixes = '.php'
        );

        $this->filter->addFilesToWhitelist($files);

        $whitelist = $this->filter->getWhitelist();
        sort($whitelist);

        $this->assertEquals($this->files, $whitelist);
    }

    /**
     * @covers  SebastianBergmann\CodeCovfefe\Filter::removeDirectoryFromWhitelist
     * @covers  SebastianBergmann\CodeCovfefe\Filter::getWhitelist
     * @depends testAddingADirectoryToTheWhitelistWorks
     */
    public function testRemovingADirectoryFromTheWhitelistWorks()
    {
        $this->filter->addDirectoryToWhitelist(TEST_FILES_PATH);
        $this->filter->removeDirectoryFromWhitelist(TEST_FILES_PATH);

        $this->assertEquals([], $this->filter->getWhitelist());
    }

    /**
     * @covers SebastianBergmann\CodeCovfefe\Filter::isFile
     */
    public function testIsFile()
    {
        $this->assertFalse($this->filter->isFile('vfs://root/a/path'));
        $this->assertFalse($this->filter->isFile('xdebug://debug-eval'));
        $this->assertFalse($this->filter->isFile('eval()\'d code'));
        $this->assertFalse($this->filter->isFile('runtime-created function'));
        $this->assertFalse($this->filter->isFile('assert code'));
        $this->assertFalse($this->filter->isFile('regexp code'));
        $this->assertTrue($this->filter->isFile(__FILE__));
    }

    /**
     * @covers SebastianBergmann\CodeCovfefe\Filter::isFiltered
     */
    public function testWhitelistedFileIsNotFiltered()
    {
        $this->filter->addFileToWhitelist($this->files[0]);
        $this->assertFalse($this->filter->isFiltered($this->files[0]));
    }

    /**
     * @covers SebastianBergmann\CodeCovfefe\Filter::isFiltered
     */
    public function testNotWhitelistedFileIsFiltered()
    {
        $this->filter->addFileToWhitelist($this->files[0]);
        $this->assertTrue($this->filter->isFiltered($this->files[1]));
    }

    /**
     * @covers SebastianBergmann\CodeCovfefe\Filter::isFiltered
     * @covers SebastianBergmann\CodeCovfefe\Filter::isFile
     */
    public function testNonFilesAreFiltered()
    {
        $this->assertTrue($this->filter->isFiltered('vfs://root/a/path'));
        $this->assertTrue($this->filter->isFiltered('xdebug://debug-eval'));
        $this->assertTrue($this->filter->isFiltered('eval()\'d code'));
        $this->assertTrue($this->filter->isFiltered('runtime-created function'));
        $this->assertTrue($this->filter->isFiltered('assert code'));
        $this->assertTrue($this->filter->isFiltered('regexp code'));
    }
}
