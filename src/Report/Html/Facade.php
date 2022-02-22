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
use function substr;
use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Directory as DirectoryUtil;
use SebastianBergmann\CodeCoverage\InvalidArgumentException;
use SebastianBergmann\CodeCoverage\Node\Directory as DirectoryNode;
use SebastianBergmann\Template\Template;

final class Facade
{
    private string $templatePath;

    private string $generator;

    private int $lowUpperBound;

    private int $highLowerBound;

    private string $colorSuccessLow;

    private string $colorSuccessMedium;

    private string $colorSuccessHigh;

    private string $colorWarning;

    private string $colorDanger;

    public function __construct(int $lowUpperBound = 50, int $highLowerBound = 90, string $generator = '', string $colorSuccessLow = '#dff0d8', string $colorSuccessMedium = '#c3e3b5', string $colorSuccessHigh = '#99cb84', string $colorWarning = '#fcf8e3', string $colorDanger = '#f2dede')
    {
        if ($lowUpperBound > $highLowerBound) {
            throw new InvalidArgumentException(
                '$lowUpperBound must not be larger than $highLowerBound'
            );
        }

        $this->generator          = $generator;
        $this->highLowerBound     = $highLowerBound;
        $this->lowUpperBound      = $lowUpperBound;
        $this->colorSuccessLow    = $colorSuccessLow;
        $this->colorSuccessMedium = $colorSuccessMedium;
        $this->colorSuccessHigh   = $colorSuccessHigh;
        $this->colorWarning       = $colorWarning;
        $this->colorDanger        = $colorDanger;
        $this->templatePath       = __DIR__ . '/Renderer/Template/';
    }

    public function process(CodeCoverage $coverage, string $target): void
    {
        $target = $this->directory($target);
        $report = $coverage->getReport();
        $date   = date('D M j G:i:s T Y');

        $dashboard = new Dashboard(
            $this->templatePath,
            $this->generator,
            $date,
            $this->lowUpperBound,
            $this->highLowerBound,
            $coverage->collectsBranchAndPathCoverage()
        );

        $directory = new Directory(
            $this->templatePath,
            $this->generator,
            $date,
            $this->lowUpperBound,
            $this->highLowerBound,
            $coverage->collectsBranchAndPathCoverage()
        );

        $file = new File(
            $this->templatePath,
            $this->generator,
            $date,
            $this->lowUpperBound,
            $this->highLowerBound,
            $coverage->collectsBranchAndPathCoverage()
        );

        $directory->render($report, $target . 'index.html');
        $dashboard->render($report, $target . 'dashboard.html');

        foreach ($report as $node) {
            $id = $node->id();

            if ($node instanceof DirectoryNode) {
                DirectoryUtil::create($target . $id);

                $directory->render($node, $target . $id . '/index.html');
                $dashboard->render($node, $target . $id . '/dashboard.html');
            } else {
                $dir = dirname($target . $id);

                DirectoryUtil::create($dir);

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
        copy($this->templatePath . 'css/nv.d3.min.css', $dir . 'nv.d3.min.css');
        copy($this->templatePath . 'css/custom.css', $dir . 'custom.css');
        copy($this->templatePath . 'css/octicons.css', $dir . 'octicons.css');

        $dir = $this->directory($target . '_icons');
        copy($this->templatePath . 'icons/file-code.svg', $dir . 'file-code.svg');
        copy($this->templatePath . 'icons/file-directory.svg', $dir . 'file-directory.svg');

        $dir = $this->directory($target . '_js');
        copy($this->templatePath . 'js/bootstrap.min.js', $dir . 'bootstrap.min.js');
        copy($this->templatePath . 'js/popper.min.js', $dir . 'popper.min.js');
        copy($this->templatePath . 'js/d3.min.js', $dir . 'd3.min.js');
        copy($this->templatePath . 'js/jquery.min.js', $dir . 'jquery.min.js');
        copy($this->templatePath . 'js/nv.d3.min.js', $dir . 'nv.d3.min.js');
        copy($this->templatePath . 'js/file.js', $dir . 'file.js');
    }

    private function renderCss(string $target): void
    {
        $template = new Template($this->templatePath . 'css/style.css', '{{', '}}');

        $template->setVar(
            [
                'success-low'    => $this->colorSuccessLow,
                'success-medium' => $this->colorSuccessMedium,
                'success-high'   => $this->colorSuccessHigh,
                'warning'        => $this->colorWarning,
                'danger'         => $this->colorDanger,
            ]
        );

        $template->renderTo($this->directory($target . '_css') . 'style.css');
    }

    private function directory(string $directory): string
    {
        if (substr($directory, -1, 1) != DIRECTORY_SEPARATOR) {
            $directory .= DIRECTORY_SEPARATOR;
        }

        DirectoryUtil::create($directory);

        return $directory;
    }
}
