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

use function file_get_contents;
use function is_file;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\CodeCoverage\Data\ProcessedClassType;
use SebastianBergmann\CodeCoverage\Data\ProcessedMethodType;
use SebastianBergmann\CodeCoverage\Node\Directory;
use SebastianBergmann\CodeCoverage\Node\File;
use SebastianBergmann\CodeCoverage\Report\Html\ClassView\Node\ClassNode;
use SebastianBergmann\CodeCoverage\Report\Html\ClassView\Node\NamespaceNode;
use SebastianBergmann\CodeCoverage\Report\Thresholds;
use SebastianBergmann\CodeCoverage\StaticAnalysis\LinesOfCode;

#[CoversClass(Namespace_::class)]
#[Small]
final class Namespace_Test extends TestCase
{
    private string $outputFile = '';

    protected function tearDown(): void
    {
        if ($this->outputFile !== '' && is_file($this->outputFile)) {
            unlink($this->outputFile);
        }
    }

    public function testRendersRootNamespaceWithChildAndClass(): void
    {
        $renderer = $this->createRenderer(false);

        $rootNs = new NamespaceNode('(Global)', '');
        $rootNs->promoteToRoot();
        $appNs = new NamespaceNode('App', 'App', $rootNs);
        $rootNs->addNamespace($appNs);

        $rootNs->addClass($this->createClassNode($rootNs, 'App\\Root'));

        $renderer->render($rootNs, $this->outputFile());

        $output = file_get_contents($this->outputFile);

        $this->assertNotFalse($output);

        $this->assertStringContainsString('Total', $output);
        $this->assertStringContainsString('<a href="App/index.html">App</a>', $output);
        $this->assertStringContainsString('<a href="Root.html">Root</a>', $output);
    }

    public function testRendersNestedNamespaceBreadcrumbs(): void
    {
        $renderer = $this->createRenderer(false);

        $rootNs = new NamespaceNode('(Global)', '');
        $rootNs->promoteToRoot();
        $appNs    = new NamespaceNode('App', 'App', $rootNs);
        $modelsNs = new NamespaceNode('Models', 'App\\Models', $appNs);
        $rootNs->addNamespace($appNs);
        $appNs->addNamespace($modelsNs);

        $modelsNs->addClass($this->createClassNode($modelsNs, 'App\\Models\\User'));

        $renderer->render($modelsNs, $this->outputFile());

        $output = file_get_contents($this->outputFile);

        $this->assertNotFalse($output);

        // Inactive breadcrumbs for ancestors
        $this->assertStringContainsString('<li class="breadcrumb-item"><a href="../../index.html">(Global)</a></li>', $output);
        $this->assertStringContainsString('<li class="breadcrumb-item"><a href="../index.html">App</a></li>', $output);
        // Active breadcrumb for current node
        $this->assertStringContainsString('<li class="breadcrumb-item active">Models</li>', $output);
        // pathToRoot uses parent depth + _classes level
        $this->assertStringContainsString('../../../_css/', $output);
    }

    public function testRendersWithBranchCoverage(): void
    {
        $renderer = $this->createRenderer(true);

        $rootNs = new NamespaceNode('(Global)', '');
        $rootNs->promoteToRoot();
        $rootNs->addClass($this->createClassNode($rootNs, 'Root'));

        $renderer->render($rootNs, $this->outputFile());

        $this->assertFileExists($this->outputFile);
    }

    private function createRenderer(bool $hasBranchCoverage): Namespace_
    {
        return new Namespace_(
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

    /**
     * @param non-empty-string $className
     */
    private function createClassNode(NamespaceNode $parent, string $className): ClassNode
    {
        $root   = new Directory('root');
        $method = new ProcessedMethodType(
            'doSomething',
            'public',
            'public function doSomething(): void',
            1,
            5,
            3,
            3,
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
            $className,
            '',
            ['doSomething' => $method],
            1,
            3,
            3,
            0,
            0,
            0,
            0,
            1,
            100,
            1,
            '',
        );
        $fileNode = new File('t.php', $root, 'a', [], [], [], [], [], [], new LinesOfCode(0, 0, 0));

        return new ClassNode($className, '', '/t.php', 1, 10, $processedClass, $fileNode, [], [], $parent);
    }
}
