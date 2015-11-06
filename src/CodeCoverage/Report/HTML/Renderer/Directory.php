<?php
/*
 * This file is part of the PHP_CodeCoverage package.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Renders a PHP_CodeCoverage_Report_Node_Directory node.
 *
 * @since Class available since Release 1.1.0
 */
class PHP_CodeCoverage_Report_HTML_Renderer_Directory extends PHP_CodeCoverage_Report_HTML_Renderer
{
    /**
     * @param PHP_CodeCoverage_Report_Node_Directory $node
     * @param string                                 $file
     */
    public function render(PHP_CodeCoverage_Report_Node_Directory $node, $file)
    {
        $template = new Text_Template($this->templatePath . 'directory.html', '{{', '}}');

        $this->setCommonTemplateVariables($template, $node);

        $items = $this->renderItem($node, true);

        foreach ($node->getDirectories() as $item) {
            $items .= $this->renderItem($item);
        }

        foreach ($node->getFiles() as $item) {
            $items .= $this->renderItem($item);
        }

        $template->setVar(
            [
                'id'    => $node->getId(),
                'items' => $items
            ]
        );

        $template->renderTo($file);
    }

    /**
     * @param  PHP_CodeCoverage_Report_Node $item
     * @param  bool                         $total
     * @return string
     */
    protected function renderItem(PHP_CodeCoverage_Report_Node $item, $total = false)
    {
        $data = [
            'numClasses'                   => $item->getNumClassesAndTraits(),
            'numTestedClasses'             => $item->getNumTestedClassesAndTraits(),
            'numMethods'                   => $item->getNumMethods(),
            'numTestedMethods'             => $item->getNumTestedMethods(),
            'linesExecutedPercent'         => $item->getLineExecutedPercent(false),
            'linesExecutedPercentAsString' => $item->getLineExecutedPercent(),
            'numExecutedLines'             => $item->getNumExecutedLines(),
            'numExecutableLines'           => $item->getNumExecutableLines(),
            'pathsExecutedPercent'         => $item->getPathExecutedPercent(false),
            'pathsExecutedPercentAsString' => $item->getPathExecutedPercent(),
            'numExecutedPaths'             => $item->getNumExecutedPaths(),
            'numExecutablePaths'           => $item->getNumExecutablePaths(),
            'testedMethodsPercent'         => $item->getTestedMethodsPercent(false),
            'testedMethodsPercentAsString' => $item->getTestedMethodsPercent(),
            'testedClassesPercent'         => $item->getTestedClassesAndTraitsPercent(false),
            'testedClassesPercentAsString' => $item->getTestedClassesAndTraitsPercent()
        ];

        if ($total) {
            $data['name'] = 'Total';
        } else {
            if ($item instanceof PHP_CodeCoverage_Report_Node_Directory) {
                $data['name'] = sprintf(
                    '<a href="%s/index.html">%s</a>',
                    $item->getName(),
                    $item->getName()
                );

                $data['icon'] = '<span class="glyphicon glyphicon-folder-open"></span> ';
            } else {
                $data['name'] = sprintf(
                    '<a href="%s.html">%s</a>',
                    $item->getName(),
                    $item->getName()
                );

                $data['icon'] = '<span class="glyphicon glyphicon-file"></span> ';
            }
        }

        return $this->renderItemTemplate(
            new Text_Template($this->templatePath . 'directory_item.html', '{{', '}}'),
            $data
        );
    }
}
