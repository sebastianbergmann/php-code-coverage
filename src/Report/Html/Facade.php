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
use function copy;
use function date;
use function dirname;
use function str_ends_with;
use SebastianBergmann\CodeCoverage\FileCouldNotBeWrittenException;
use SebastianBergmann\CodeCoverage\Node\Directory as DirectoryNode;
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

        $dashboard = new Dashboard(
            $this->templatePath,
            $this->generator,
            $date,
            $this->thresholds,
            $hasBranchCoverage,
        );

        $directory = new Directory(
            $this->templatePath,
            $this->generator,
            $date,
            $this->thresholds,
            $hasBranchCoverage,
        );

        $file = new File(
            $this->templatePath,
            $this->generator,
            $date,
            $this->thresholds,
            $hasBranchCoverage,
        );

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

        $this->copyFiles($target);
        $this->renderCss($target);
    }

    private function copyFiles(string $target): void
    {
        $dir = $this->directory($target . '_css');

        copy($this->templatePath . 'css/bootstrap.min.css', $dir . 'bootstrap.min.css');
        copy($this->customCssFile->path(), $dir . 'custom.css');
        copy($this->templatePath . 'css/octicons.css', $dir . 'octicons.css');

        $dir = $this->directory($target . '_icons');
        copy($this->templatePath . 'icons/file-code.svg', $dir . 'file-code.svg');
        copy($this->templatePath . 'icons/file-directory.svg', $dir . 'file-directory.svg');

        $dir = $this->directory($target . '_js');
        copy($this->templatePath . 'js/bootstrap.bundle.min.js', $dir . 'bootstrap.bundle.min.js');
        copy($this->templatePath . 'js/jquery.min.js', $dir . 'jquery.min.js');
        copy($this->templatePath . 'js/file.js', $dir . 'file.js');
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
