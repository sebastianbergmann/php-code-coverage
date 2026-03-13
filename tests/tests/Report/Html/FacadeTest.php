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
use function file_get_contents;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use SebastianBergmann\CodeCoverage\Node\Directory as DirectoryNode;
use SebastianBergmann\CodeCoverage\Node\File as FileNode;
use SebastianBergmann\CodeCoverage\StaticAnalysis\Class_;
use SebastianBergmann\CodeCoverage\StaticAnalysis\LinesOfCode;
use SebastianBergmann\CodeCoverage\StaticAnalysis\Method;
use SebastianBergmann\CodeCoverage\StaticAnalysis\Visibility;
use SebastianBergmann\CodeCoverage\TestCase;

#[CoversClass(Facade::class)]
#[Medium]
final class FacadeTest extends TestCase
{
    protected function tearDown(): void
    {
        $this->removeTemporaryFiles();
    }

    public function testProcessRendersFilesInSubdirectoriesAndClassesInNestedNamespaces(): void
    {
        $target = TEST_FILES_PATH . 'tmp' . DIRECTORY_SEPARATOR;
        $report = $this->buildReportWithNestedNamespacesAndSubdirectory();

        (new Facade)->process($report, $target);

        // renderFileView: subdirectory iteration (Facade.php lines 86-90)
        $this->assertFileExists($target . 'sub/index.html');
        $this->assertFileExists($target . 'sub/dashboard.html');
        $this->assertFileExists($target . 'sub/User.php.html');
        $this->assertFileExists($target . 'sub/HomeController.php.html');
        $this->assertFileExists($target . 'GlobalClass.php.html');

        // renderClassView: nested namespace NamespaceNode iteration (lines 114-119)
        $this->assertFileExists($target . '_classes/App/index.html');
        $this->assertFileExists($target . '_classes/App/dashboard.html');
        $this->assertFileExists($target . '_classes/App/Models/index.html');
        $this->assertFileExists($target . '_classes/App/Controllers/index.html');

        // renderClassView: class page in nested namespace directory (lines 126-127)
        $this->assertFileExists($target . '_classes/App/Models/User.html');
        $this->assertFileExists($target . '_classes/App/Controllers/HomeController.html');

        // Global-namespace class page sits directly under _classes
        $this->assertFileExists($target . '_classes/GlobalClass.html');

        // buildFileToClassMap: nested-namespace class path (line 158)
        $fileHtml = file_get_contents($target . 'sub/User.php.html');

        $this->assertNotFalse($fileHtml);
        $this->assertStringContainsString('_classes/App/Models/User.html', $fileHtml);
    }

    private function buildReportWithNestedNamespacesAndSubdirectory(): DirectoryNode
    {
        $rootPath = TEST_FILES_PATH . 'FacadeNested';
        $root     = new DirectoryNode($rootPath);
        $subDir   = $root->addDirectory('sub');

        $root->addFile($this->createGlobalClassFile($root));
        $subDir->addFile($this->createUserFile($subDir));
        $subDir->addFile($this->createHomeControllerFile($subDir));

        return $root;
    }

    private function createGlobalClassFile(DirectoryNode $parent): FileNode
    {
        $method = new Method(
            'doSomething',
            5,
            7,
            'public function doSomething(): void',
            Visibility::Public,
            1,
        );

        $rawClass = new Class_(
            'GlobalClass',
            'GlobalClass',
            '',
            TEST_FILES_PATH . 'FacadeNested/GlobalClass.php',
            3,
            8,
            null,
            [],
            [],
            ['doSomething' => $method],
        );

        return new FileNode(
            'GlobalClass.php',
            $parent,
            'a1',
            [],
            [],
            [],
            ['GlobalClass' => $rawClass],
            [],
            [],
            new LinesOfCode(9, 0, 9),
        );
    }

    private function createUserFile(DirectoryNode $parent): FileNode
    {
        $method = new Method(
            'save',
            7,
            9,
            'public function save(): void',
            Visibility::Public,
            1,
        );

        $rawClass = new Class_(
            'User',
            'App\\Models\\User',
            'App\\Models',
            TEST_FILES_PATH . 'FacadeNested/sub/User.php',
            5,
            10,
            null,
            [],
            [],
            ['save' => $method],
        );

        return new FileNode(
            'User.php',
            $parent,
            'b2',
            [],
            [],
            [],
            ['App\\Models\\User' => $rawClass],
            [],
            [],
            new LinesOfCode(11, 0, 11),
        );
    }

    private function createHomeControllerFile(DirectoryNode $parent): FileNode
    {
        $method = new Method(
            'index',
            7,
            9,
            'public function index(): void',
            Visibility::Public,
            1,
        );

        $rawClass = new Class_(
            'HomeController',
            'App\\Controllers\\HomeController',
            'App\\Controllers',
            TEST_FILES_PATH . 'FacadeNested/sub/HomeController.php',
            5,
            10,
            null,
            [],
            [],
            ['index' => $method],
        );

        return new FileNode(
            'HomeController.php',
            $parent,
            'c3',
            [],
            [],
            [],
            ['App\\Controllers\\HomeController' => $rawClass],
            [],
            [],
            new LinesOfCode(11, 0, 11),
        );
    }
}
