<?php
/**
 * PHP_CodeCoverage
 *
 * Copyright (c) 2009-2011, Sebastian Bergmann <sb@sebastian-bergmann.de>.
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
 * @author     Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @copyright  2009-2011 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link       http://github.com/sebastianbergmann/php-code-coverage
 * @since      File available since Release 1.1.0
 */

/**
 * Renders a PHP_CodeCoverage_Report_Node_Directory node.
 *
 * @category   PHP
 * @package    CodeCoverage
 * @author     Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @copyright  2009-2011 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version    Release: @package_version@
 * @link       http://github.com/sebastianbergmann/php-code-coverage
 * @since      Class available since Release 1.1.0
 */
class PHP_CodeCoverage_Report_HTML_Renderer_Directory extends PHP_CodeCoverage_Report_HTML_Renderer
{
    /**
     * @param PHP_CodeCoverage_Report_Node_Directory $node
     * @param string                                 $file
     * @param string                                 $title
     */
    public function render(PHP_CodeCoverage_Report_Node_Directory $node, $file, $title = NULL)
    {
        if ($title === NULL) {
            $title = $node->getName();
        }

        $template = new Text_Template($this->templatePath . 'directory.html');

        $this->setCommonTemplateVariables($template, $title, $node);

        $items = $this->renderItem($node, TRUE);

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
    protected function renderItem(PHP_CodeCoverage_Report_Node $item, $total = FALSE)
    {
        $template = new Text_Template(
          $this->templatePath . 'directory_item.html'
        );

        if ($total) {
            $icon      = '';
            $itemClass = 'coverDirectory';
            $name      = 'Total';
        } else {
            $name = sprintf(
              '<a href="%s.html">%s</a>',
              $item->getId(),
              $item->getName()
            );

            if ($item instanceof PHP_CodeCoverage_Report_Node_Directory) {
                $icon      = '<img alt="directory" src="directory.png"/> ';
                $itemClass = 'coverDirectory';
            } else {
                $icon      = '<img alt="file" src="file.png"/> ';
                $itemClass = 'coverFile';
            }
        }

        $numClasses           = $item->getNumClasses();
        $testedClassesPercent = floor($item->getTestedClassesPercent(FALSE));

        if ($numClasses > 0) {
            list($classesColor, $classesLevel) = $this->getColorLevel(
              $testedClassesPercent
            );

            $classesNumber = $item->getNumTestedClasses() . ' / ' . $numClasses;
        } else {
            $classesColor  = 'snow';
            $classesLevel  = 'None';
            $classesNumber = '&nbsp;';
        }

        $numMethods           = $item->getNumMethods();
        $testedMethodsPercent = floor($item->getTestedMethodsPercent(FALSE));

        if ($numMethods > 0) {
            list($methodsColor, $methodsLevel) = $this->getColorLevel(
              $testedMethodsPercent
            );

            $methodsNumber = $item->getNumTestedMethods() . ' / ' . $numMethods;
        } else {
            $methodsColor  = 'snow';
            $methodsLevel  = 'None';
            $methodsNumber = '&nbsp;';
        }

        $linesExecutedPercent = floor($item->getLineExecutedPercent(FALSE));

        list($linesColor, $linesLevel) = $this->getColorLevel(
          $linesExecutedPercent
        );

        $template->setVar(
          array(
            'itemClass' => $itemClass,
            'icon' => $icon,
            'name' => $name,
            'lines_color' => $linesColor,
            'lines_executed_width' => $linesExecutedPercent,
            'lines_not_executed_width' => 100 - $linesExecutedPercent,
            'lines_executed_percent' => $item->getLineExecutedPercent(),
            'lines_level' => $linesLevel,
            'num_executed_lines' => $item->getNumExecutedLines(),
            'num_executable_lines' => $item->getNumExecutableLines(),
            'methods_color' => $methodsColor,
            'methods_tested_width' => $testedMethodsPercent,
            'methods_not_tested_width' => 100 - $testedMethodsPercent,
            'methods_tested_percent' => $item->getTestedMethodsPercent(),
            'methods_level' => $methodsLevel,
            'methods_number' => $methodsNumber,
            'classes_color' => $classesColor,
            'classes_tested_width' => $testedClassesPercent,
            'classes_not_tested_width' => 100 - $testedClassesPercent,
            'classes_tested_percent' => $item->getTestedClassesPercent(),
            'classes_level' => $classesLevel,
            'classes_number' => $classesNumber
          )
        );

        return $template->render();
    }

    /**
     * @param  integer $percent
     * @return array
     */
    protected function getColorLevel($percent)
    {
        if ($percent < $this->lowUpperBound) {
            $color = 'scarlet_red';
            $level = 'Lo';
        }

        else if ($percent >= $this->lowUpperBound &&
                 $percent <  $this->highLowerBound) {
            $color = 'butter';
            $level = 'Med';
        }

        else {
            $color = 'chameleon';
            $level = 'Hi';
        }

        return array($color, $level);
    }
}
