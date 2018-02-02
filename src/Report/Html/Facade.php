<?php
/*
 * This file is part of the php-code-coverage package.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SebastianBergmann\CodeCoverage\Report\Html;

use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Node\Directory as DirectoryNode;
use SebastianBergmann\CodeCoverage\RuntimeException;

/**
 * Generates an HTML report from a code coverage object.
 */
final class Facade
{
    /**
     * @var string
     */
    private $templatePath;

    /**
     * @var string
     */
    private $generator;

    /**
     * @var int
     */
    private $lowUpperBound;

    /**
     * @var int
     */
    private $highLowerBound;

    public function __construct(int $lowUpperBound = 50, int $highLowerBound = 90, string $generator = '')
    {
        $this->generator      = $generator;
        $this->highLowerBound = $highLowerBound;
        $this->lowUpperBound  = $lowUpperBound;
        $this->templatePath   = __DIR__ . '/Renderer/Template/';
    }

    /**
     * @throws RuntimeException
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function process(CodeCoverage $coverage, string $target): void
    {
        $target = $this->getDirectory($target);
        $report = $coverage->getReport();

        if (!isset($_SERVER['REQUEST_TIME'])) {
            $_SERVER['REQUEST_TIME'] = \time();
        }

        $date = \date('D M j G:i:s T Y', $_SERVER['REQUEST_TIME']);

        $dashboard = new Dashboard(
            $this->templatePath,
            $this->generator,
            $date,
            $this->lowUpperBound,
            $this->highLowerBound
        );

        $directory = new Directory(
            $this->templatePath,
            $this->generator,
            $date,
            $this->lowUpperBound,
            $this->highLowerBound
        );

        $file = new File(
            $this->templatePath,
            $this->generator,
            $date,
            $this->lowUpperBound,
            $this->highLowerBound
        );

        $directory->render($report, $target . 'index.html');
        $dashboard->render($report, $target . 'dashboard.html');

        foreach ($report as $node) {
            $id = $node->getId();

            if ($node instanceof DirectoryNode) {
                if (!@\mkdir($target . $id, 0777, true) && !\is_dir($target . $id)) {
                    throw new \RuntimeException(\sprintf('Directory "%s" was not created', $target . $id));
                }

                $directory->render($node, $target . $id . '/index.html');
                $dashboard->render($node, $target . $id . '/dashboard.html');
            } else {
                $dir = \dirname($target . $id);

                if (!@\mkdir($dir, 0777, true) && !\is_dir($dir)) {
                    throw new \RuntimeException(\sprintf('Directory "%s" was not created', $dir));
                }

                $file->render($node, $target . $id . '.html');
            }
        }

        $this->copyFiles($target);
    }

    /**
     * @throws RuntimeException
     */
    private function copyFiles(string $target): void
    {
        $dir = $this->getDirectory($target . '.css');

        \file_put_contents(
            $dir . 'bootstrap.min.css',
            \str_replace(
                'url(../fonts/',
                'url(../.fonts/',
                \file_get_contents($this->templatePath . 'css/bootstrap.min.css')
            )

        );

        \copy($this->templatePath . 'css/nv.d3.min.css', $dir . 'nv.d3.min.css');
        \copy($this->templatePath . 'css/style.css', $dir . 'style.css');

        $dir = $this->getDirectory($target . '.fonts');
        \copy($this->templatePath . 'fonts/glyphicons-halflings-regular.eot', $dir . 'glyphicons-halflings-regular.eot');
        \copy($this->templatePath . 'fonts/glyphicons-halflings-regular.svg', $dir . 'glyphicons-halflings-regular.svg');
        \copy($this->templatePath . 'fonts/glyphicons-halflings-regular.ttf', $dir . 'glyphicons-halflings-regular.ttf');
        \copy($this->templatePath . 'fonts/glyphicons-halflings-regular.woff', $dir . 'glyphicons-halflings-regular.woff');
        \copy($this->templatePath . 'fonts/glyphicons-halflings-regular.woff2', $dir . 'glyphicons-halflings-regular.woff2');

        $dir = $this->getDirectory($target . '.js');
        \copy($this->templatePath . 'js/bootstrap.min.js', $dir . 'bootstrap.min.js');
        \copy($this->templatePath . 'js/d3.min.js', $dir . 'd3.min.js');
        \copy($this->templatePath . 'js/holder.min.js', $dir . 'holder.min.js');
        \copy($this->templatePath . 'js/html5shiv.min.js', $dir . 'html5shiv.min.js');
        \copy($this->templatePath . 'js/jquery.min.js', $dir . 'jquery.min.js');
        \copy($this->templatePath . 'js/nv.d3.min.js', $dir . 'nv.d3.min.js');
        \copy($this->templatePath . 'js/respond.min.js', $dir . 'respond.min.js');
        \copy($this->templatePath . 'js/file.js', $dir . 'file.js');
    }

    /**
     * @throws RuntimeException
     */
    private function getDirectory(string $directory): string
    {
        if (\substr($directory, -1, 1) != DIRECTORY_SEPARATOR) {
            $directory .= DIRECTORY_SEPARATOR;
        }

        if (!@\mkdir($directory, 0777, true) && !\is_dir($directory)) {
            throw new RuntimeException(
                \sprintf(
                    'Directory "%s" does not exist.',
                    $directory
                )
            );
        }

        return $directory;
    }
}
