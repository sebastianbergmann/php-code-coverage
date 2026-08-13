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
use function array_key_exists;
use function assert;
use function copy;
use function date;
use function dirname;
use function str_ends_with;
use SebastianBergmann\CodeCoverage\FileCouldNotBeWrittenException;
use SebastianBergmann\CodeCoverage\Node\AbstractNode;
use SebastianBergmann\CodeCoverage\Node\Directory as DirectoryNode;
use SebastianBergmann\CodeCoverage\Node\File as FileNode;
use SebastianBergmann\CodeCoverage\Report\Html\ClassView\Builder;
use SebastianBergmann\CodeCoverage\Report\Html\ClassView\Node\ClassNode;
use SebastianBergmann\CodeCoverage\Report\Html\ClassView\Node\NamespaceNode;
use SebastianBergmann\CodeCoverage\Report\Html\ClassView\Renderer\Class_ as ClassRenderer;
use SebastianBergmann\CodeCoverage\Report\Html\ClassView\Renderer\Dashboard as ClassDashboard;
use SebastianBergmann\CodeCoverage\Report\Html\ClassView\Renderer\Namespace_ as NamespaceRenderer;
use SebastianBergmann\CodeCoverage\Report\Thresholds;
use SebastianBergmann\CodeCoverage\Test\TestSizes;
use SebastianBergmann\CodeCoverage\Util\Filesystem;
use SebastianBergmann\Template\Exception;
use SebastianBergmann\Template\Template;

/**
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for phpunit/php-code-coverage
 */
final readonly class Facade
{
    private string $templatePath;
    private string $generator;
    private Colors $colors;
    private Thresholds $thresholds;
    private CustomCssFile $customCssFile;
    private Views $views;

    public function __construct(string $generator = '', ?Colors $colors = null, ?Thresholds $thresholds = null, ?CustomCssFile $customCssFile = null, Views $views = Views::FileViewAndClassView)
    {
        $this->generator     = $generator;
        $this->colors        = $colors ?? Colors::default();
        $this->thresholds    = $thresholds ?? Thresholds::default();
        $this->customCssFile = $customCssFile ?? CustomCssFile::default();
        $this->views         = $views;
        $this->templatePath  = __DIR__ . '/Renderer/Template/';
    }

    public function process(DirectoryNode $report, string $target): void
    {
        $target            = $this->directory($target);
        $date              = date('D M j G:i:s T Y');
        $hasBranchCoverage = $report->numberOfExecutableBranches() > 0;
        $hasPathCoverage   = $report->numberOfExecutablePaths() > 0;
        $testSizes         = $this->testSizes($report);

        if ($this->views->classView()) {
            $rootNamespace = new Builder()->build($report);
        }

        if ($this->views->fileView()) {
            $fileToClassMap = isset($rootNamespace) ? $this->buildFileToClassMap($rootNamespace) : [];

            $this->renderFileView($report, $target, $date, $hasBranchCoverage, $hasPathCoverage, $testSizes, $fileToClassMap);
        }

        if (isset($rootNamespace)) {
            $this->renderClassView($rootNamespace, $target, $date, $hasBranchCoverage, $hasPathCoverage, $testSizes);
        }

        $this->copyFiles($target);
        $this->renderCss($target);
    }

    /**
     * The test sizes for which the report has coverage data.
     *
     * A test that did not cover any code is not part of the coverage data, so
     * a test size for which no line was executed is a test size that cannot be
     * filtered by in a meaningful way.
     *
     * @return int<0, 7>
     */
    private function testSizes(DirectoryNode $report): int
    {
        $testSizes = 0;

        foreach ([TestSizes::SMALL, TestSizes::MEDIUM, TestSizes::LARGE] as $testSize) {
            if ($report->numberOfExecutedLinesByTestSize($testSize) > 0) {
                $testSizes |= $testSize;
            }
        }

        return $testSizes;
    }

    /**
     * @param int<0, 7>             $testSizes
     * @param array<string, string> $fileToClassMap
     */
    private function renderFileView(DirectoryNode $report, string $target, string $date, bool $hasBranchCoverage, bool $hasPathCoverage, int $testSizes, array $fileToClassMap): void
    {
        $dashboard = new Dashboard($this->templatePath, $this->generator, $date, $this->thresholds, $hasBranchCoverage, $hasPathCoverage, $testSizes, $this->views);
        $directory = new Directory($this->templatePath, $this->generator, $date, $this->thresholds, $hasBranchCoverage, $hasPathCoverage, $testSizes, $this->views);
        $file      = new File($this->templatePath, $this->generator, $date, $this->thresholds, $hasBranchCoverage, $hasPathCoverage, $testSizes, $this->views);

        $file->setFileToClassMap($fileToClassMap);

        $directory->render($report, $target . 'index.html');
        $dashboard->render($report, $target . 'dashboard.html');

        foreach ($report as $node) {
            assert($node instanceof AbstractNode);

            $id = $node->id();

            if ($node instanceof DirectoryNode) {
                Filesystem::createDirectory($target . $id);

                $directory->render($node, $target . $id . '/index.html');
                $dashboard->render($node, $target . $id . '/dashboard.html');
            } elseif ($node instanceof FileNode) {
                $dir = dirname($target . $id);

                Filesystem::createDirectory($dir);

                $file->render($node, $target . $id);
            }
        }
    }

    /**
     * @param int<0, 7> $testSizes
     */
    private function renderClassView(NamespaceNode $rootNamespace, string $target, string $date, bool $hasBranchCoverage, bool $hasPathCoverage, int $testSizes): void
    {
        $classTarget = $this->views->fileView() ? $this->directory($target . '_classes') : $target;

        $namespaceRenderer = new NamespaceRenderer($this->templatePath, $this->generator, $date, $this->thresholds, $hasBranchCoverage, $hasPathCoverage, $testSizes, $this->views);
        $classRenderer     = new ClassRenderer($this->templatePath, $this->generator, $date, $this->thresholds, $hasBranchCoverage, $hasPathCoverage, $testSizes, $this->views);
        $dashboard         = new ClassDashboard($this->templatePath, $this->generator, $date, $this->thresholds, $hasBranchCoverage, $hasPathCoverage, $testSizes, $this->views);

        $namespaceRenderer->render($rootNamespace, $classTarget . 'index.html');
        $dashboard->render($rootNamespace, $classTarget . 'dashboard.html');

        foreach ($rootNamespace->iterate() as $node) {
            if ($node instanceof NamespaceNode) {
                $id = $node->id();

                Filesystem::createDirectory($classTarget . $id);

                $namespaceRenderer->render($node, $classTarget . $id . '/index.html');
                $dashboard->render($node, $classTarget . $id . '/dashboard.html');
            } elseif ($node instanceof ClassNode) {
                $nsId = $node->parent()->id();

                if ($nsId === 'index') {
                    $dir = $classTarget;
                } else {
                    $dir = $classTarget . $nsId . '/';
                    Filesystem::createDirectory($dir);
                }

                $classRenderer->render($node, $dir . $node->shortName() . '.html');
            }
        }
    }

    /**
     * @return array<string, string>
     */
    private function buildFileToClassMap(NamespaceNode $rootNamespace): array
    {
        $map = [];

        foreach ($rootNamespace->iterate() as $node) {
            if (!$node instanceof ClassNode) {
                continue;
            }

            $fileId = $node->fileNode()->id();

            if (array_key_exists($fileId, $map)) {
                continue;
            }

            $nsId = $node->parent()->id();

            if ($nsId === 'index') {
                $classPagePath = '_classes/' . $node->shortName() . '.html';
            } else {
                $classPagePath = '_classes/' . $nsId . '/' . $node->shortName() . '.html';
            }

            $map[$fileId] = $classPagePath;
        }

        return $map;
    }

    private function copyFiles(string $target): void
    {
        copy($this->customCssFile->path(), $this->directory($target . '_css') . 'custom.css');

        $dir = $this->directory($target . '_js');

        copy($this->templatePath . 'js/coverage-table.js', $dir . 'coverage-table.js');
        copy($this->templatePath . 'js/source-view.js', $dir . 'source-view.js');
    }

    private function renderCss(string $target): void
    {
        $template = new Template($this->templatePath . 'css/style.css', '{{', '}}');

        $template->setVar(
            [
                'breadcrumbs'         => $this->colors->breadcrumbs(),
                'breadcrumbs-dark'    => $this->colors->breadcrumbsDark(),
                'success-bar'         => $this->colors->successBar(),
                'success-bar-dark'    => $this->colors->successBarDark(),
                'success-high'        => $this->colors->successHigh(),
                'success-high-dark'   => $this->colors->successHighDark(),
                'success-medium'      => $this->colors->successMedium(),
                'success-medium-dark' => $this->colors->successMediumDark(),
                'success-low'         => $this->colors->successLow(),
                'success-low-dark'    => $this->colors->successLowDark(),
                'warning'             => $this->colors->warning(),
                'warning-dark'        => $this->colors->warningDark(),
                'warning-bar'         => $this->colors->warningBar(),
                'warning-bar-dark'    => $this->colors->warningBarDark(),
                'danger'              => $this->colors->danger(),
                'danger-dark'         => $this->colors->dangerDark(),
                'danger-bar'          => $this->colors->dangerBar(),
                'danger-bar-dark'     => $this->colors->dangerBarDark(),
            ],
        );

        try {
            $template->renderTo($this->directory($target . '_css') . 'style.css');
            // @codeCoverageIgnoreStart
        } catch (Exception $e) {
            throw new FileCouldNotBeWrittenException(
                $e->getMessage(),
                $e->getCode(),
                $e,
            );
            // @codeCoverageIgnoreEnd
        }
    }

    private function directory(string $directory): string
    {
        if (!str_ends_with($directory, DIRECTORY_SEPARATOR)) {
            $directory .= DIRECTORY_SEPARATOR;
        }

        Filesystem::createDirectory($directory);

        return $directory;
    }
}
