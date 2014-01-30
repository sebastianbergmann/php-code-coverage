<?php
/**
 * PHP_CodeCoverage
 *
 * Copyright (c) 2009-2014, Sebastian Bergmann <sebastian@phpunit.de>.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *   * Neither the name of Sebastian Bergmann nor the names of his
 *     contributors may be used to endorse or promote products derived
 *     from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @category   PHP
 * @package    CodeCoverage
 * @author     Sebastian Bergmann <sebastian@phpunit.de>
 * @copyright  2009-2014 Sebastian Bergmann <sebastian@phpunit.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @link       http://github.com/sebastianbergmann/php-code-coverage
 * @since      File available since Release 1.1.0
 */

/**
 * Renders a PHP_CodeCoverage_Report_Node_Directory node.
 *
 * @category   PHP
 * @package    CodeCoverage
 * @author     Sebastian Bergmann <sebastian@phpunit.de>
 * @copyright  2009-2014 Sebastian Bergmann <sebastian@phpunit.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @link       http://github.com/sebastianbergmann/php-code-coverage
 * @since      Class available since Release 1.1.0
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
            array(
                'id'    => $node->getId(),
                'items' => $items
            )
        );

        $template->renderTo($file);
    }

    /**
     * @param  PHP_CodeCoverage_Report_Node $item
     * @param  boolean                      $total
     * @return string
     */
    protected function renderItem(PHP_CodeCoverage_Report_Node $item, $total = false)
    {
        $data = array(
            'numClasses'                   => $item->getNumClassesAndTraits(),
            'numTestedClasses'             => $item->getNumTestedClassesAndTraits(),
            'numMethods'                   => $item->getNumMethods(),
            'numTestedMethods'             => $item->getNumTestedMethods(),
            'linesExecutedPercent'         => $item->getLineExecutedPercent(false),
            'linesExecutedPercentAsString' => $item->getLineExecutedPercent(),
            'numExecutedLines'             => $item->getNumExecutedLines(),
            'numExecutableLines'           => $item->getNumExecutableLines(),
            'testedMethodsPercent'         => $item->getTestedMethodsPercent(false),
            'testedMethodsPercentAsString' => $item->getTestedMethodsPercent(),
            'testedClassesPercent'         => $item->getTestedClassesAndTraitsPercent(false),
            'testedClassesPercentAsString' => $item->getTestedClassesAndTraitsPercent()
        );

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
