<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\Report\Html\ClassView\Renderer;

use const DIRECTORY_SEPARATOR;
use function array_filter;
use function array_unique;
use function array_values;
use function file_get_contents;
use function is_file;
use function preg_match_all;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\CodeCoverage\Data\ProcessedClassType;
use SebastianBergmann\CodeCoverage\Data\ProcessedMethodType;
use SebastianBergmann\CodeCoverage\Data\ProcessedTraitType;
use SebastianBergmann\CodeCoverage\Node\Directory;
use SebastianBergmann\CodeCoverage\Node\File;
use SebastianBergmann\CodeCoverage\Report\Html\ClassView\Node\ClassNode;
use SebastianBergmann\CodeCoverage\Report\Html\ClassView\Node\NamespaceNode;
use SebastianBergmann\CodeCoverage\Report\Html\ClassView\Node\ParentSection;
use SebastianBergmann\CodeCoverage\Report\Html\ClassView\Node\TraitSection;
use SebastianBergmann\CodeCoverage\Report\Thresholds;
use SebastianBergmann\CodeCoverage\StaticAnalysis\LinesOfCode;

#[CoversClass(Class_::class)]
#[Small]
final class Class_Test extends TestCase
{
    private string $outputFile = '';

    protected function tearDown(): void
    {
        if ($this->outputFile !== '' && is_file($this->outputFile)) {
            unlink($this->outputFile);
        }
    }

    public function testRendersClassWithTraitAndParentSectionsInNestedNamespace(): void
    {
        $renderer = $this->createRenderer(false);
        $node     = $this->createClassNodeWithTraitAndParent();

        $renderer->render($node, $this->outputFile());

        $output = file_get_contents($this->outputFile);

        $this->assertNotFalse($output);

        $this->assertStringContainsString('App\\Models\\ChildClass', $output);
        $this->assertStringContainsString('From Example\\ExampleTrait', $output);
        $this->assertStringContainsString('Inherited from Example\\ParentClass', $output);
        $this->assertStringContainsString('[Example\\ExampleTrait]', $output);
        $this->assertStringContainsString('[Example\\ParentClass]', $output);
        // pathToRoot uses one extra level for nested namespace + _classes
        $this->assertStringContainsString('../../../_css/', $output);

        // Line anchors in trait and parent sections are prefixed so that they do not
        // collide with the line anchors of the class's own source section
        $this->assertStringContainsString('id="trait-0-5"', $output);
        $this->assertStringContainsString('href="#trait-0-', $output);
        $this->assertStringContainsString('id="parent-0-', $output);
        $this->assertStringContainsString('href="#parent-0-', $output);

        preg_match_all('/ id="([^"]+)"/', $output, $matches);

        $anchors = array_values(
            array_filter(
                $matches[1],
                static fn (string $id): bool => $id !== 'code',
            ),
        );

        $this->assertSame(array_values(array_unique($anchors)), $anchors);
    }

    public function testRendersClassWithBranchCoverageTemplate(): void
    {
        $renderer = $this->createRenderer(true);
        $node     = $this->createClassNodeWithTraitAndParent();

        $renderer->render($node, $this->outputFile());

        $output = file_get_contents($this->outputFile);

        $this->assertNotFalse($output);

        $this->assertStringContainsString('App\\Models\\ChildClass', $output);
    }

    public function testMarksLinesCoveredBySmallAndMediumTests(): void
    {
        $renderer = $this->createRenderer(false);

        $rootDir = new Directory('root');
        $method  = new ProcessedMethodType(
            'doSomething',
            'public',
            'public function doSomething(): void',
            7,
            10,
            2,
            2,
            0,
            0,
            0,
            0,
            1,
            100,
            1,
            '',
        );
        $processedClass = new ProcessedClassType(
            'Example\\ExampleClass',
            'Example',
            ['doSomething' => $method],
            5,
            2,
            2,
            0,
            0,
            0,
            0,
            1,
            100,
            1,
            '',
        );

        $filePath         = TEST_FILES_PATH . 'ClassView' . DIRECTORY_SEPARATOR . 'ExampleClass.php';
        $lineCoverageData = [
            7 => ['SmallTest'],
            9 => ['MediumTest'],
        ];
        $testData = [
            'SmallTest'  => ['size' => 'small', 'status' => 'passed', 'time' => 0.0],
            'MediumTest' => ['size' => 'medium', 'status' => 'passed', 'time' => 0.0],
        ];

        $fileNode = new File(
            'ExampleClass.php',
            $rootDir,
            'abc',
            $lineCoverageData,
            [],
            $testData,
            [],
            [],
            [],
            new LinesOfCode(16, 0, 16),
        );

        $parent = new NamespaceNode('Example', 'Example');
        $parent->promoteToRoot();

        $classNode = new ClassNode(
            'Example\\ExampleClass',
            'Example',
            $filePath,
            5,
            16,
            $processedClass,
            $fileNode,
            [],
            [],
            $parent,
        );

        $renderer->render($classNode, $this->outputFile());

        $output = file_get_contents($this->outputFile);

        $this->assertNotFalse($output);

        $this->assertStringContainsString('covered-by-small-tests', $output);
        $this->assertStringContainsString('covered-by-medium-tests', $output);
    }

    public function testSkipsLinesOutsideOfSourceFile(): void
    {
        $renderer = $this->createRenderer(false);

        $rootDir = new Directory('root');
        $method  = new ProcessedMethodType(
            'doSomething',
            'public',
            'public function doSomething(): void',
            7,
            10,
            0,
            0,
            0,
            0,
            0,
            0,
            1,
            0,
            1,
            '',
        );
        $processedClass = new ProcessedClassType(
            'Example\\ExampleClass',
            'Example',
            ['doSomething' => $method],
            5,
            0,
            0,
            0,
            0,
            0,
            0,
            1,
            0,
            1,
            '',
        );

        $filePath = TEST_FILES_PATH . 'ClassView' . DIRECTORY_SEPARATOR . 'ExampleClass.php';
        $fileNode = new File(
            'ExampleClass.php',
            $rootDir,
            'abc',
            [],
            [],
            [],
            [],
            [],
            [],
            new LinesOfCode(16, 0, 16),
        );

        $parent = new NamespaceNode('Example', 'Example');
        $parent->promoteToRoot();

        // endLine is far past the actual end of the file, forcing the `!isset($codeLines[$lineIndex])` branch
        $classNode = new ClassNode(
            'Example\\ExampleClass',
            'Example',
            $filePath,
            1,
            9999,
            $processedClass,
            $fileNode,
            [],
            [],
            $parent,
        );

        $renderer->render($classNode, $this->outputFile());

        $this->assertFileExists($this->outputFile);
    }

    private function createRenderer(bool $hasBranchCoverage): Class_
    {
        return new Class_(
            __DIR__ . '/../../../../../../src/Report/Html/Renderer/Template/',
            'test-generator',
            'Jan 1 00:00:00 UTC 2026',
            Thresholds::default(),
            $hasBranchCoverage,
            false,
        );
    }

    private function outputFile(): string
    {
        $this->outputFile = tempnam(sys_get_temp_dir(), 'cov_');

        return $this->outputFile;
    }

    private function createClassNodeWithTraitAndParent(): ClassNode
    {
        $root = new Directory('root');

        $classMethod = new ProcessedMethodType(
            'childMethod',
            'public',
            'public function childMethod(): void',
            9,
            12,
            1,
            1,
            0,
            0,
            0,
            0,
            1,
            100,
            1,
            '',
        );

        $traitMethod = new ProcessedMethodType(
            'traitMethod',
            'public',
            'public function traitMethod(): string',
            7,
            10,
            1,
            1,
            0,
            0,
            0,
            0,
            1,
            100,
            1,
            '',
        );

        $parentMethod = new ProcessedMethodType(
            'parentMethod',
            'public',
            'public function parentMethod(): void',
            7,
            10,
            1,
            0,
            0,
            0,
            0,
            0,
            1,
            0,
            1,
            '',
        );

        $processedClass = new ProcessedClassType(
            'App\\Models\\ChildClass',
            'App\\Models',
            ['childMethod' => $classMethod],
            5,
            1,
            1,
            0,
            0,
            0,
            0,
            1,
            100,
            1,
            '',
        );

        $processedTrait = new ProcessedTraitType(
            'Example\\ExampleTrait',
            'Example',
            ['traitMethod' => $traitMethod],
            5,
            1,
            1,
            0,
            0,
            0,
            0,
            1,
            100,
            1,
            '',
        );

        $classFilePath  = TEST_FILES_PATH . 'ClassView' . DIRECTORY_SEPARATOR . 'ChildClass.php';
        $traitFilePath  = TEST_FILES_PATH . 'ClassView' . DIRECTORY_SEPARATOR . 'ExampleTrait.php';
        $parentFilePath = TEST_FILES_PATH . 'ClassView' . DIRECTORY_SEPARATOR . 'ParentClass.php';

        $classFileNode  = new File('ChildClass.php', $root, 'a', [], [], [], [], [], [], new LinesOfCode(13, 0, 13));
        $traitFileNode  = new File('ExampleTrait.php', $root, 'b', [], [], [], [], [], [], new LinesOfCode(11, 0, 11));
        $parentFileNode = new File('ParentClass.php', $root, 'c', [], [], [], [], [], [], new LinesOfCode(11, 0, 11));

        $traitSection  = new TraitSection('Example\\ExampleTrait', $traitFilePath, 5, 11, $processedTrait, $traitFileNode);
        $parentSection = new ParentSection('Example\\ParentClass', $parentFilePath, ['parentMethod' => $parentMethod], $parentFileNode);

        // Build nested namespace: index (root) -> App -> Models
        $rootNs = new NamespaceNode('(Global)', '');
        $rootNs->promoteToRoot();
        $appNs    = new NamespaceNode('App', 'App', $rootNs);
        $modelsNs = new NamespaceNode('Models', 'App\\Models', $appNs);
        $rootNs->addNamespace($appNs);
        $appNs->addNamespace($modelsNs);

        return new ClassNode(
            'App\\Models\\ChildClass',
            'App\\Models',
            $classFilePath,
            5,
            13,
            $processedClass,
            $classFileNode,
            [$traitSection],
            [$parentSection],
            $modelsNs,
        );
    }
}
