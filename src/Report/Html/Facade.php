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
use function copy;
use function date;
use function dirname;
use function str_ends_with;
use SebastianBergmann\CodeCoverage\FileCouldNotBeWrittenException;
use SebastianBergmann\CodeCoverage\Node\Directory as DirectoryNode;
use SebastianBergmann\CodeCoverage\Report\Html\ClassView\Builder;
use SebastianBergmann\CodeCoverage\Report\Html\ClassView\Node\ClassNode;
use SebastianBergmann\CodeCoverage\Report\Html\ClassView\Node\NamespaceNode;
use SebastianBergmann\CodeCoverage\Report\Html\ClassView\Renderer\Class_ as ClassRenderer;
use SebastianBergmann\CodeCoverage\Report\Html\ClassView\Renderer\Dashboard as ClassDashboard;
use SebastianBergmann\CodeCoverage\Report\Html\ClassView\Renderer\Namespace_ as NamespaceRenderer;
use SebastianBergmann\CodeCoverage\Report\Thresholds;
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

    public function __construct(string $generator = '', ?Colors $colors = null, ?Thresholds $thresholds = null, ?CustomCssFile $customCssFile = null)
    {
        $this->generator     = $generator;
        $this->colors        = $colors ?? Colors::default();
        $this->thresholds    = $thresholds ?? Thresholds::default();
        $this->customCssFile = $customCssFile ?? CustomCssFile::default();
        $this->templatePath  = __DIR__ . '/Renderer/Template/';
    }

    public function process(DirectoryNode $report, string $target): void
    {
        $target            = $this->directory($target);
        $date              = date('D M j G:i:s T Y');
        $hasBranchCoverage = $report->numberOfExecutableBranches() > 0;

        $builder        = new Builder;
        $rootNamespace  = $builder->build($report);
        $fileToClassMap = $this->buildFileToClassMap($rootNamespace);

        $this->renderFileView($report, $target, $date, $hasBranchCoverage, $fileToClassMap);
        $this->renderClassView($rootNamespace, $target, $date, $hasBranchCoverage);
        $this->copyFiles($target);
        $this->renderCss($target);
    }

    /**
     * @param array<string, string> $fileToClassMap
     */
    private function renderFileView(DirectoryNode $report, string $target, string $date, bool $hasBranchCoverage, array $fileToClassMap): void
    {
        $dashboard = new Dashboard($this->templatePath, $this->generator, $date, $this->thresholds, $hasBranchCoverage);
        $directory = new Directory($this->templatePath, $this->generator, $date, $this->thresholds, $hasBranchCoverage);
        $file      = new File($this->templatePath, $this->generator, $date, $this->thresholds, $hasBranchCoverage);

        $file->setFileToClassMap($fileToClassMap);

        $directory->render($report, $target . 'index.html');
        $dashboard->render($report, $target . 'dashboard.html');

        foreach ($report as $node) {
            $id = $node->id();

            if ($node instanceof DirectoryNode) {
                Filesystem::createDirectory($target . $id);

                $directory->render($node, $target . $id . '/index.html');
                $dashboard->render($node, $target . $id . '/dashboard.html');
            } else {
                $dir = dirname($target . $id);

                Filesystem::createDirectory($dir);

                $file->render($node, $target . $id);
            }
        }
    }

    private function renderClassView(NamespaceNode $rootNamespace, string $target, string $date, bool $hasBranchCoverage): void
    {
        $classTarget = $this->directory($target . '_classes');

        $namespaceRenderer = new NamespaceRenderer($this->templatePath, $this->generator, $date, $this->thresholds, $hasBranchCoverage);
        $classRenderer     = new ClassRenderer($this->templatePath, $this->generator, $date, $this->thresholds, $hasBranchCoverage);
        $dashboard         = new ClassDashboard($this->templatePath, $this->generator, $date, $this->thresholds, $hasBranchCoverage);

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
        $dir = $this->directory($target . '_css');

        copy($this->templatePath . 'css/billboard.min.css', $dir . 'billboard.min.css');
        copy($this->templatePath . 'css/bootstrap.min.css', $dir . 'bootstrap.min.css');
        copy($this->customCssFile->path(), $dir . 'custom.css');
        copy($this->templatePath . 'css/octicons.css', $dir . 'octicons.css');

        $dir = $this->directory($target . '_icons');
        copy($this->templatePath . 'icons/file-code.svg', $dir . 'file-code.svg');
        copy($this->templatePath . 'icons/file-directory.svg', $dir . 'file-directory.svg');

        $dir = $this->directory($target . '_js');
        copy($this->templatePath . 'js/billboard.pkgd.min.js', $dir . 'billboard.pkgd.min.js');
        copy($this->templatePath . 'js/bootstrap.bundle.min.js', $dir . 'bootstrap.bundle.min.js');
        copy($this->templatePath . 'js/jquery.min.js', $dir . 'jquery.min.js');
        copy($this->templatePath . 'js/file.js', $dir . 'file.js');
    }

    private function renderCss(string $target): void
    {
        $template = new Template($this->templatePath . 'css/style.css', '{{', '}}');

        $template->setVar(
            [
                'success-low'    => $this->colors->successLow(),
                'success-medium' => $this->colors->successMedium(),
                'success-high'   => $this->colors->successHigh(),
                'warning'        => $this->colors->warning(),
                'danger'         => $this->colors->danger(),
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
