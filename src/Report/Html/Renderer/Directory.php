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

use function sprintf;
use function usort;
use SebastianBergmann\CodeCoverage\FileCouldNotBeWrittenException;
use SebastianBergmann\CodeCoverage\Node\AbstractNode as Node;
use SebastianBergmann\CodeCoverage\Node\Directory as DirectoryNode;
use SebastianBergmann\CodeCoverage\Node\File as FileNode;
use SebastianBergmann\Template\Exception;
use SebastianBergmann\Template\Template;

/**
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @phpstan-import-type CoverageItemData from \SebastianBergmann\CodeCoverage\Report\Html\Renderer
 */
final class Directory extends Renderer
{
    public function render(DirectoryNode $node, string $file): void
    {
        $template = new Template($this->templateNameForTier('directory'), '{{', '}}');

        $this->setCommonTemplateVariables($template, $node);

        $items = '';

        $directories = $node->directories();

        usort(
            $directories,
            static fn (DirectoryNode $a, DirectoryNode $b) => $a->name() <=> $b->name(),
        );

        foreach ($directories as $item) {
            $items .= $this->renderItem($item);
        }

        $files = $node->files();

        usort(
            $files,
            static fn (FileNode $a, FileNode $b) => $a->name() <=> $b->name(),
        );

        foreach ($files as $item) {
            $items .= $this->renderItem($item);
        }

        $template->setVar(
            [
                'id'      => $node->id(),
                'items'   => $items,
                'summary' => $this->renderSummary($this->itemData($node), 'Functions and Methods', 'Classes and Traits'),
                'legend'  => $this->thresholdsLegend(),
            ],
        );

        try {
            $template->renderTo($file);
        } catch (Exception $e) {
            throw new FileCouldNotBeWrittenException(
                $e->getMessage(),
                $e->getCode(),
                $e,
            );
        }
    }

    /**
     * @return CoverageItemData
     */
    private function itemData(Node $node): array
    {
        return [
            'name'                              => '',
            'numClasses'                        => $node->numberOfClassesAndTraits(),
            'numTestedClasses'                  => $node->numberOfTestedClassesAndTraits(),
            'numMethods'                        => $node->numberOfFunctionsAndMethods(),
            'numTestedMethods'                  => $node->numberOfTestedFunctionsAndMethods(),
            'linesExecutedPercent'              => $node->percentageOfExecutedLines()->asFloat(),
            'linesExecutedPercentAsString'      => $node->percentageOfExecutedLines()->asString(),
            'numExecutedLines'                  => $node->numberOfExecutedLines(),
            'numExecutableLines'                => $node->numberOfExecutableLines(),
            'branchesExecutedPercent'           => $node->percentageOfExecutedBranches()->asFloat(),
            'branchesExecutedPercentAsString'   => $node->percentageOfExecutedBranches()->asString(),
            'numExecutedBranches'               => $node->numberOfExecutedBranches(),
            'numExecutableBranches'             => $node->numberOfExecutableBranches(),
            'pathsExecutedPercent'              => $node->percentageOfExecutedPaths()->asFloat(),
            'pathsExecutedPercentAsString'      => $node->percentageOfExecutedPaths()->asString(),
            'numExecutedPaths'                  => $node->numberOfExecutedPaths(),
            'numExecutablePaths'                => $node->numberOfExecutablePaths(),
            'testedMethodsPercent'              => $node->percentageOfTestedFunctionsAndMethods()->asFloat(),
            'testedMethodsPercentAsString'      => $node->percentageOfTestedFunctionsAndMethods()->asString(),
            'testedClassesPercent'              => $node->percentageOfTestedClassesAndTraits()->asFloat(),
            'testedClassesPercentAsString'      => $node->percentageOfTestedClassesAndTraits()->asString(),
            'numFilesWithoutBranchCoverageData' => $node->numberOfFilesWithoutBranchCoverageData(),
            'coverageDataJson'                  => $this->coverageDataJsonFor($node),
        ];
    }

    private function renderItem(Node $node): string
    {
        $name = $this->escapeHtml($node->name());
        $data = $this->itemData($node);

        if ($node instanceof DirectoryNode) {
            $data['icon'] = '<span class="icon icon-directory" aria-hidden="true"></span>';
            $data['name'] = sprintf('<a href="%s/index.html">%s</a>', $name, $name);
        } else {
            $data['icon'] = '<span class="icon icon-file" aria-hidden="true"></span>';
            $data['name'] = sprintf('<a href="%s.html">%s</a>', $name, $name);
        }

        return $this->renderItemTemplate(
            $this->template($this->templateNameForTier('directory_item')),
            $data,
        );
    }
}
