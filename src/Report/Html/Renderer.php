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

use const ENT_HTML5;
use const ENT_QUOTES;
use const ENT_SUBSTITUTE;
use function count;
use function htmlspecialchars;
use function sprintf;
use function str_repeat;
use function substr_count;
use SebastianBergmann\CodeCoverage\Node\AbstractNode;
use SebastianBergmann\CodeCoverage\Node\Directory as DirectoryNode;
use SebastianBergmann\CodeCoverage\Node\File as FileNode;
use SebastianBergmann\CodeCoverage\Report\Thresholds;
use SebastianBergmann\CodeCoverage\Version;
use SebastianBergmann\Environment\Runtime;
use SebastianBergmann\Template\Template;

/**
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @phpstan-type CoverageItemData array{
 *     name: string,
 *     numClasses?: int,
 *     numTestedClasses?: int,
 *     testedClassesPercent?: float,
 *     testedClassesPercentAsString?: string,
 *     numMethods: int,
 *     numTestedMethods: int,
 *     testedMethodsPercent: float,
 *     testedMethodsPercentAsString: string,
 *     numExecutableLines: int,
 *     numExecutedLines: int,
 *     linesExecutedPercent: float,
 *     linesExecutedPercentAsString: string,
 *     numExecutableBranches: int,
 *     numExecutedBranches: int,
 *     branchesExecutedPercent: float,
 *     branchesExecutedPercentAsString: string,
 *     numExecutablePaths: int,
 *     numExecutedPaths: int,
 *     pathsExecutedPercent: float,
 *     pathsExecutedPercentAsString: string,
 *     numFilesWithoutBranchCoverageData?: int,
 *     icon?: string,
 *     crap?: int|string,
 * }
 */
abstract class Renderer
{
    protected string $templatePath;
    protected string $generator;
    protected string $date;
    protected Thresholds $thresholds;
    protected bool $hasBranchCoverage;
    protected bool $hasPathCoverage;
    protected string $version;

    public function __construct(string $templatePath, string $generator, string $date, Thresholds $thresholds, bool $hasBranchCoverage, bool $hasPathCoverage)
    {
        $this->templatePath      = $templatePath;
        $this->generator         = $generator;
        $this->date              = $date;
        $this->thresholds        = $thresholds;
        $this->version           = Version::id();
        $this->hasBranchCoverage = $hasBranchCoverage;
        $this->hasPathCoverage   = $hasPathCoverage;
    }

    /**
     * @return non-empty-string
     */
    protected function templateNameForTier(string $base): string
    {
        if ($this->hasPathCoverage) {
            return $this->templatePath . $base . '_branch_and_path.html';
        }

        if ($this->hasBranchCoverage) {
            return $this->templatePath . $base . '_branch.html';
        }

        return $this->templatePath . $base . '.html';
    }

    /**
     * @param CoverageItemData $data
     */
    protected function renderItemTemplate(Template $template, array $data): string
    {
        $numSeparator = '&nbsp;/&nbsp;';

        if (isset($data['numClasses']) && $data['numClasses'] > 0) {
            $classesLevel = $this->colorLevel($data['testedClassesPercent'] ?? 0.0);

            $classesNumber = ($data['numTestedClasses'] ?? 0) . $numSeparator .
                $data['numClasses'];

            $classesBar = $this->coverageBar(
                $data['testedClassesPercent'] ?? 0.0,
            );
        } else {
            $classesLevel                         = '';
            $classesNumber                        = '0' . $numSeparator . '0';
            $classesBar                           = '';
            $data['testedClassesPercentAsString'] = 'n/a';
        }

        if ($data['numMethods'] > 0) {
            $methodsLevel = $this->colorLevel($data['testedMethodsPercent']);

            $methodsNumber = $data['numTestedMethods'] . $numSeparator .
                $data['numMethods'];

            $methodsBar = $this->coverageBar(
                $data['testedMethodsPercent'],
            );
        } else {
            $methodsLevel                         = '';
            $methodsNumber                        = '0' . $numSeparator . '0';
            $methodsBar                           = '';
            $data['testedMethodsPercentAsString'] = 'n/a';
        }

        if ($data['numExecutableLines'] > 0) {
            $linesLevel = $this->colorLevel($data['linesExecutedPercent']);

            $linesNumber = $data['numExecutedLines'] . $numSeparator .
                $data['numExecutableLines'];

            $linesBar = $this->coverageBar(
                $data['linesExecutedPercent'],
            );
        } else {
            $linesLevel                           = '';
            $linesNumber                          = '0' . $numSeparator . '0';
            $linesBar                             = '';
            $data['linesExecutedPercentAsString'] = 'n/a';
        }

        $numFilesWithoutBranchCoverageData = $data['numFilesWithoutBranchCoverageData'] ?? 0;

        if ($data['numExecutablePaths'] > 0) {
            $pathsLevel = $this->colorLevel($data['pathsExecutedPercent']);

            $pathsNumber = $data['numExecutedPaths'] . $numSeparator .
                $data['numExecutablePaths'];

            $pathsBar = $this->coverageBar(
                $data['pathsExecutedPercent'],
            );

            if ($numFilesWithoutBranchCoverageData > 0) {
                $data['pathsExecutedPercentAsString'] .= ' <abbr title="Not all files have branch and path coverage data">*</abbr>';
            }
        } else {
            $pathsLevel                           = '';
            $pathsNumber                          = '0' . $numSeparator . '0';
            $pathsBar                             = '';
            $data['pathsExecutedPercentAsString'] = 'n/a';
        }

        if ($data['numExecutableBranches'] > 0) {
            $branchesLevel = $this->colorLevel($data['branchesExecutedPercent']);

            $branchesNumber = $data['numExecutedBranches'] . $numSeparator .
                $data['numExecutableBranches'];

            $branchesBar = $this->coverageBar(
                $data['branchesExecutedPercent'],
            );

            if ($numFilesWithoutBranchCoverageData > 0) {
                $data['branchesExecutedPercentAsString'] .= ' <abbr title="Not all files have branch and path coverage data">*</abbr>';
            }
        } else {
            $branchesLevel                           = '';
            $branchesNumber                          = '0' . $numSeparator . '0';
            $branchesBar                             = '';
            $data['branchesExecutedPercentAsString'] = 'n/a';
        }

        $template->setVar(
            [
                'icon'                      => $data['icon'] ?? '',
                'crap'                      => (string) ($data['crap'] ?? ''),
                'name'                      => $data['name'],
                'lines_bar'                 => $linesBar,
                'lines_executed_percent'    => $data['linesExecutedPercentAsString'],
                'lines_level'               => $linesLevel,
                'lines_number'              => $linesNumber,
                'paths_bar'                 => $pathsBar,
                'paths_executed_percent'    => $data['pathsExecutedPercentAsString'],
                'paths_level'               => $pathsLevel,
                'paths_number'              => $pathsNumber,
                'branches_bar'              => $branchesBar,
                'branches_executed_percent' => $data['branchesExecutedPercentAsString'],
                'branches_level'            => $branchesLevel,
                'branches_number'           => $branchesNumber,
                'methods_bar'               => $methodsBar,
                'methods_tested_percent'    => $data['testedMethodsPercentAsString'],
                'methods_level'             => $methodsLevel,
                'methods_number'            => $methodsNumber,
                'classes_bar'               => $classesBar,
                'classes_tested_percent'    => $data['testedClassesPercentAsString'] ?? '',
                'classes_level'             => $classesLevel,
                'classes_number'            => $classesNumber,
            ],
        );

        return $template->render();
    }

    protected function setCommonTemplateVariables(Template $template, AbstractNode $node): void
    {
        $template->setVar(
            [
                'id'               => $node->id(),
                'full_path'        => $this->escapeHtml($node->pathAsString()),
                'path_to_root'     => $this->pathToRoot($node),
                'breadcrumbs'      => $this->breadcrumbs($node),
                'date'             => $this->date,
                'version'          => $this->version,
                'runtime'          => $this->runtimeString(),
                'generator'        => $this->generator,
                'low_upper_bound'  => (string) $this->thresholds->lowUpperBound(),
                'high_lower_bound' => (string) $this->thresholds->highLowerBound(),
            ],
        );
    }

    protected function escapeHtml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5);
    }

    protected function breadcrumbs(AbstractNode $node): string
    {
        $breadcrumbs = '';
        $path        = $node->pathAsArray();
        $depth       = count($path);

        if ($node instanceof FileNode) {
            $depth--;
        }

        foreach ($path as $step) {
            if ($step !== $node) {
                $depth--;

                $breadcrumbs .= $this->inactiveBreadcrumb(
                    $step,
                    str_repeat('../', $depth),
                );
            } else {
                $breadcrumbs .= $this->activeBreadcrumb($step);
            }
        }

        return $breadcrumbs;
    }

    protected function activeBreadcrumb(AbstractNode $node): string
    {
        $buffer = sprintf(
            '         <li class="breadcrumb-item active">%s</li>' . "\n",
            $this->escapeHtml($node->name()),
        );

        if ($node instanceof DirectoryNode) {
            $buffer .= '         <li class="breadcrumb-item">(<a href="dashboard.html">Dashboard</a>)</li>' . "\n";
        }

        return $buffer;
    }

    protected function inactiveBreadcrumb(AbstractNode $node, string $pathToRoot): string
    {
        return sprintf(
            '         <li class="breadcrumb-item"><a href="%sindex.html">%s</a></li>' . "\n",
            $pathToRoot,
            $this->escapeHtml($node->name()),
        );
    }

    protected function pathToRoot(AbstractNode $node): string
    {
        $id    = $node->id();
        $depth = substr_count($id, '/');

        if ($id !== 'index' &&
            $node instanceof DirectoryNode) {
            $depth++;
        }

        return str_repeat('../', $depth);
    }

    protected function coverageBar(float $percent): string
    {
        $level = $this->colorLevel($percent);

        $template = new Template(
            $this->templatePath . 'coverage_bar.html',
            '{{',
            '}}',
        );

        $template->setVar(['level' => $level, 'percent' => sprintf('%.2F', $percent)]);

        return $template->render();
    }

    protected function colorLevel(float $percent): string
    {
        if ($percent <= $this->thresholds->lowUpperBound()) {
            return 'danger';
        }

        if ($percent > $this->thresholds->lowUpperBound() &&
            $percent < $this->thresholds->highLowerBound()) {
            return 'warning';
        }

        return 'success';
    }

    private function runtimeString(): string
    {
        $runtime = new Runtime;

        return sprintf(
            '<a href="%s" target="_top">%s %s</a>',
            $runtime->getVendorUrl(),
            $runtime->getName(),
            $runtime->getVersion(),
        );
    }
}
