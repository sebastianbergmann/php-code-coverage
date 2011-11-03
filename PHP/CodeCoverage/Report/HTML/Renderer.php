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
 * Base class for PHP_CodeCoverage_Report_Node renderers.
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
abstract class PHP_CodeCoverage_Report_HTML_Renderer
{
    /**
     * @var string
     */
    protected $templatePath;

    /**
     * @var string
     */
    protected $charset;

    /**
     * @var string
     */
    protected $generator;

    /**
     * @var string
     */
    protected $date;

    /**
     * @var integer
     */
    protected $lowUpperBound;

    /**
     * @var integer
     */
    protected $highLowerBound;

    /**
     * Constructor.
     *
     * @param string  $templatePath
     * @param string  $charset
     * @param string  $generator
     * @param string  $date
     * @param integer $lowUpperBound
     * @param integer $highLowerBound
     */
    public function __construct($templatePath, $charset, $generator, $date, $lowUpperBound, $highLowerBound)
    {
        $this->templatePath   = $templatePath;
        $this->charset        = $charset;
        $this->generator      = $generator;
        $this->date           = $date;
        $this->lowUpperBound  = $lowUpperBound;
        $this->highLowerBound = $highLowerBound;
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

    /**
     * @param  Text_Template $template
     * @param  array         $data
     * @return string
     */
    protected function renderItemTemplate(Text_Template $template, array $data)
    {
        if (isset($data['numClasses']) && $data['numClasses'] > 0) {
            list($classesColor, $classesLevel) = $this->getColorLevel(
              $data['testedClassesPercent']
            );

            $classesNumber = $data['numTestedClasses'] . ' / ' .
                             $data['numClasses'];
        } else {
            $classesColor  = 'snow';
            $classesLevel  = 'None';
            $classesNumber = '&nbsp;';
        }

        if ($data['numMethods'] > 0) {
            list($methodsColor, $methodsLevel) = $this->getColorLevel(
              $data['testedMethodsPercent']
            );

            $methodsNumber = $data['numTestedMethods'] . ' / ' .
                             $data['numMethods'];
        } else {
            $methodsColor  = 'snow';
            $methodsLevel  = 'None';
            $methodsNumber = '&nbsp;';
        }

        list($linesColor, $linesLevel) = $this->getColorLevel(
          $data['linesExecutedPercent']
        );

        $template->setVar(
          array(
            'itemClass' => isset($data['itemClass']) ? $data['itemClass'] : '',
            'icon' => isset($data['icon']) ? $data['icon'] : '',
            'crap' => isset($data['crap']) ? $data['crap'] : '',
            'name' => $data['name'],
            'lines_color' => $linesColor,
            'lines_executed_width' => $data['linesExecutedPercent'],
            'lines_not_executed_width' => 100 - $data['linesExecutedPercent'],
            'lines_executed_percent' => $data['linesExecutedPercentAsString'],
            'lines_level' => $linesLevel,
            'num_executed_lines' => $data['numExecutedLines'],
            'num_executable_lines' => $data['numExecutableLines'],
            'methods_color' => $methodsColor,
            'methods_tested_width' => $data['testedMethodsPercent'],
            'methods_not_tested_width' => 100 - $data['testedMethodsPercent'],
            'methods_tested_percent' => $data['testedMethodsPercentAsString'],
            'methods_level' => $methodsLevel,
            'methods_number' => $methodsNumber,
            'classes_color' => $classesColor,
            'classes_tested_width' => isset($data['testedClassesPercent']) ? $data['testedClassesPercent'] : '',
            'classes_not_tested_width' => isset($data['testedClassesPercent']) ? 100 - $data['testedClassesPercent'] : '',
            'classes_tested_percent' => isset($data['testedClassesPercentAsString']) ? $data['testedClassesPercentAsString'] : '',
            'classes_level' => $classesLevel,
            'classes_number' => $classesNumber
          )
        );

        return $template->render();
    }

    /**
     * @param Text_Template                $template
     * @param string                       $title
     * @param PHP_CodeCoverage_Report_Node $node
     */
    protected function setCommonTemplateVariables(Text_Template $template, $title, PHP_CodeCoverage_Report_Node $node = NULL)
    {
        $link = '';

        if ($node !== NULL) {
            $path = $node->getPathAsArray();

            foreach ($path as $step) {
                $link .= sprintf(
                  '%s<a href="%s.html">%s</a>',
                  !empty($link) ? '/' : '',
                  $step->getId(),
                  $step->getName()
                );
            }
        }

        $template->setVar(
          array(
            'title'            => $title,
            'link'             => $link,
            'charset'          => $this->charset,
            'date'             => $this->date,
            'version'          => '@package_version@',
            'php_version'      => PHP_VERSION,
            'generator'        => $this->generator,
            'low_upper_bound'  => $this->lowUpperBound,
            'high_lower_bound' => $this->highLowerBound
          )
        );
    }
}
