<?php
/**
 * PHP_CodeCoverage
 *
 * Copyright (c) 2009-2013, Sebastian Bergmann <sebastian@phpunit.de>.
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
 * @copyright  2009-2013 Sebastian Bergmann <sebastian@phpunit.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @link       http://github.com/sebastianbergmann/php-code-coverage
 * @since      File available since Release 1.1.0
 */

/**
 * Renders the dashboard for a PHP_CodeCoverage_Report_Node_Directory node.
 *
 * @category   PHP
 * @package    CodeCoverage
 * @author     Sebastian Bergmann <sebastian@phpunit.de>
 * @copyright  2009-2013 Sebastian Bergmann <sebastian@phpunit.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @link       http://github.com/sebastianbergmann/php-code-coverage
 * @since      Class available since Release 1.1.0
 */
class PHP_CodeCoverage_Report_HTML_Renderer_Dashboard extends PHP_CodeCoverage_Report_HTML_Renderer
{
    /**
     * @param PHP_CodeCoverage_Report_Node_Directory $node
     * @param string                                 $file
     */
    public function render(PHP_CodeCoverage_Report_Node_Directory $node, $file)
    {
        $classes  = $node->getClassesAndTraits();
        $template = new Text_Template(
          $this->templatePath . 'dashboard.html'
        );

        $this->setCommonTemplateVariables($template, $node);

        $template->setVar(
          array(
            'least_tested_methods' => $this->leastTestedMethods($classes),
            'top_project_risks'    => $this->topProjectRisks($classes),
            'cc_values'            => $this->classComplexity($classes),
            'ccd_values'           => $this->classCoverageDistribution($classes),
            'backlink'             => basename(str_replace('.dashboard', '', $file))
          )
        );

        $template->renderTo($file);
    }

    /**
     * Returns the data for the Class Complexity chart.
     *
     * @param  array $classes
     * @return string
     */
    protected function classComplexity(array $classes)
    {
        $data = array();

        foreach ($classes as $name => $class) {
            $data[] = array(
              $class['coverage'],
              $class['ccn'],
              sprintf(
                '<a href="%s">%s</a>',
                $class['link'],
                $name
              )
            );
        }

        return json_encode($data);
    }

    /**
     * Returns the data for the Class Coverage Distribution chart.
     *
     * @param  array $classes
     * @return string
     */
    protected function classCoverageDistribution(array $classes)
    {
        $data = array(
          '0%'      => 0,
          '0-10%'   => 0,
          '10-20%'  => 0,
          '20-30%'  => 0,
          '30-40%'  => 0,
          '40-50%'  => 0,
          '50-60%'  => 0,
          '60-70%'  => 0,
          '70-80%'  => 0,
          '80-90%'  => 0,
          '90-100%' => 0,
          '100%'    => 0
        );

        foreach ($classes as $class) {
            if ($class['coverage'] == 0) {
                $data['0%']++;
            }

            else if ($class['coverage'] == 100) {
                $data['100%']++;
            }

            else {
                $key = floor($class['coverage']/10)*10;
                $key = $key . '-' . ($key + 10) . '%';
                $data[$key]++;
            }
        }

        return json_encode(array_values($data));
    }

    /**
     * Returns the least tested methods.
     *
     * @param  array   $classes
     * @param  integer $max
     * @return string
     */
    protected function leastTestedMethods(array $classes, $max = 10)
    {
        $methods = array();

        foreach ($classes as $className => $class) {
            foreach ($class['methods'] as $methodName => $method) {
                if ($method['coverage'] < 100) {
                    if ($className != '*') {
                        $key = $className . '::' . $methodName;
                    } else {
                        $key = $methodName;
                    }

                    $methods[$key] = $method['coverage'];
                }
            }
        }

        asort($methods);

        $methods = array_slice($methods, 0, min($max, count($methods)));
        $buffer  = '';

        foreach ($methods as $name => $coverage) {
            list($class, $method) = explode('::', $name);

            $buffer .= sprintf(
              '              <li><a href="%s">%s</a> (%d%%)</li>' . "\n",
              $classes[$class]['methods'][$method]['link'],
              $name,
              $coverage
            );
        }

        return $buffer;
    }

    /**
     * Returns the top project risks according to the CRAP index.
     *
     * @param  array   $classes
     * @param  integer $max
     * @return string
     */
    protected function topProjectRisks(array $classes, $max = 10)
    {
        $risks = array();

        foreach ($classes as $className => $class) {
            if ($class['coverage'] < 100 &&
                $class['ccn'] > count($class['methods'])) {
                $risks[$className] = $class['crap'];
            }
        }

        arsort($risks);

        $buffer = '';
        $risks  = array_slice($risks, 0, min($max, count($risks)));

        foreach ($risks as $name => $crap) {
            $buffer .= sprintf(
              '              <li><a href="%s">%s</a> (%d)</li>' . "\n",
              $classes[$name]['link'],
              $name,
              $crap
            );
        }

        return $buffer;
    }

    protected function getActiveBreadcrumb(PHP_CodeCoverage_Report_Node $node, $isDirectory)
    {
        return sprintf(
          '        <li><a href="%s.html">%s</a></li>' . "\n" .
          '        <li class="active">(Dashboard)</li>' . "\n",
          $node->getId(),
          $node->getName()
        );
    }
}
