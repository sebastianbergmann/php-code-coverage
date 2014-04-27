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
 * Renders the dashboard for a PHP_CodeCoverage_Report_Node_Directory node.
 *
 * @category   PHP
 * @package    CodeCoverage
 * @author     Sebastian Bergmann <sebastian@phpunit.de>
 * @copyright  2009-2014 Sebastian Bergmann <sebastian@phpunit.de>
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
            $this->templatePath . 'dashboard.html', '{{', '}}'
        );

        $this->setCommonTemplateVariables($template, $node);

        $complexity           = $this->complexity($classes);
        $coverageDistribution = $this->coverageDistribution($classes);
        $insufficientCoverage = $this->insufficientCoverage($classes);
        $projectRisks         = $this->projectRisks($classes);

        $template->setVar(
            array(
                'insufficient_coverage_classes' => $insufficientCoverage['class'],
                'insufficient_coverage_methods' => $insufficientCoverage['method'],
                'project_risks_classes'         => $projectRisks['class'],
                'project_risks_methods'         => $projectRisks['method'],
                'complexity_class'              => $complexity['class'],
                'complexity_method'             => $complexity['method'],
                'class_coverage_distribution'   => $coverageDistribution['class'],
                'method_coverage_distribution'  => $coverageDistribution['method'],
                'backlink'                      => basename(str_replace('.dashboard', '', $file))
            )
        );

        $template->renderTo($file);
    }

    /**
     * Returns the data for the Class/Method Complexity charts.
     *
     * @param  array $classes
     * @return array
     */
    protected function complexity(array $classes)
    {
        $result = array('class' => array(), 'method' => array());

        foreach ($classes as $className => $class) {
            foreach ($class['methods'] as $methodName => $method) {
                if ($className != '*') {
                    $methodName = $className . '::' . $methodName;
                }

                $result['method'][] = array(
                    $method['coverage'],
                    $method['ccn'],
                    sprintf(
                        '<a href="%s">%s</a>',
                        $method['link'],
                        $methodName
                    )
                );
            }

            $result['class'][] = array(
                $class['coverage'],
                $class['ccn'],
                sprintf(
                    '<a href="%s">%s</a>',
                    $class['link'],
                    $className
                )
            );
        }

        return array(
            'class' => json_encode($result['class']),
            'method' => json_encode($result['method'])
        );
    }

    /**
     * Returns the data for the Class / Method Coverage Distribution chart.
     *
     * @param  array $classes
     * @return array
     */
    protected function coverageDistribution(array $classes)
    {
        $result = array(
            'class' => array(
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
            ),
            'method' => array(
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
            )
        );

        foreach ($classes as $class) {
            foreach ($class['methods'] as $method) {
                if ($method['coverage'] == 0) {
                    $result['method']['0%']++;
                } elseif ($method['coverage'] == 100) {
                    $result['method']['100%']++;
                } else {
                    $key = floor($method['coverage'] / 10) * 10;
                    $key = $key . '-' . ($key + 10) . '%';
                    $result['method'][$key]++;
                }
            }

            if ($class['coverage'] == 0) {
                $result['class']['0%']++;
            } elseif ($class['coverage'] == 100) {
                $result['class']['100%']++;
            } else {
                $key = floor($class['coverage'] / 10) * 10;
                $key = $key . '-' . ($key + 10) . '%';
                $result['class'][$key]++;
            }
        }

        return array(
            'class' => json_encode(array_values($result['class'])),
            'method' => json_encode(array_values($result['method']))
        );
    }

    /**
     * Returns the classes / methods with insufficient coverage.
     *
     * @param  array $classes
     * @return array
     */
    protected function insufficientCoverage(array $classes)
    {
        $leastTestedClasses = array();
        $leastTestedMethods = array();
        $result             = array('class' => '', 'method' => '');

        foreach ($classes as $className => $class) {
            foreach ($class['methods'] as $methodName => $method) {
                if ($method['coverage'] < $this->highLowerBound) {
                    if ($className != '*') {
                        $key = $className . '::' . $methodName;
                    } else {
                        $key = $methodName;
                    }

                    $leastTestedMethods[$key] = $method['coverage'];
                }
            }

            if ($class['coverage'] < $this->highLowerBound) {
                $leastTestedClasses[$className] = $class['coverage'];
            }
        }

        asort($leastTestedClasses);
        asort($leastTestedMethods);

        foreach ($leastTestedClasses as $className => $coverage) {
            $result['class'] .= sprintf(
                '       <tr><td><a href="%s">%s</a></td><td class="text-right">%d%%</td></tr>' . "\n",
                $classes[$className]['link'],
                $className,
                $coverage
            );
        }

        foreach ($leastTestedMethods as $methodName => $coverage) {
            list($class, $method) = explode('::', $methodName);

            $result['method'] .= sprintf(
                '       <tr><td><a href="%s">%s</a></td><td class="text-right">%d%%</td></tr>' . "\n",
                $classes[$class]['methods'][$method]['link'],
                $methodName,
                $coverage
            );
        }

        return $result;
    }

    /**
     * Returns the project risks according to the CRAP index.
     *
     * @param  array $classes
     * @return array
     */
    protected function projectRisks(array $classes)
    {
        $classRisks  = array();
        $methodRisks = array();
        $result      = array('class' => '', 'method' => '');

        foreach ($classes as $className => $class) {
            foreach ($class['methods'] as $methodName => $method) {
                if ($method['coverage'] < $this->highLowerBound &&
                    $method['ccn'] > 1) {
                    if ($className != '*') {
                        $key = $className . '::' . $methodName;
                    } else {
                        $key = $methodName;
                    }

                    $methodRisks[$key] = $method['crap'];
                }
            }

            if ($class['coverage'] < $this->highLowerBound &&
                $class['ccn'] > count($class['methods'])) {
                $classRisks[$className] = $class['crap'];
            }
        }

        arsort($classRisks);
        arsort($methodRisks);

        foreach ($classRisks as $className => $crap) {
            $result['class'] .= sprintf(
                '       <tr><td><a href="%s">%s</a></td><td class="text-right">%d</td></tr>' . "\n",
                $classes[$className]['link'],
                $className,
                $crap
            );
        }

        foreach ($methodRisks as $methodName => $crap) {
            list($class, $method) = explode('::', $methodName);

            $result['method'] .= sprintf(
                '       <tr><td><a href="%s">%s</a></td><td class="text-right">%d</td></tr>' . "\n",
                $classes[$class]['methods'][$method]['link'],
                $methodName,
                $crap
            );
        }

        return $result;
    }

    /**
     * @param  PHP_CodeCoverage_Report_Node $node
     * @return string
     */
    protected function getActiveBreadcrumb(PHP_CodeCoverage_Report_Node $node)
    {
        return sprintf(
            '        <li><a href="%s.html">%s</a></li>' . "\n" .
            '        <li class="active">(Dashboard)</li>' . "\n",
            $node->getId(),
            //Do not always print the full absolute path of $node here:
            //optionally replace prefix
            $this->stripProjectPrefixFromNodeName($node)
        );
    }
}
