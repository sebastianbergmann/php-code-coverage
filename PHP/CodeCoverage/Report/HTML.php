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
 * @since      File available since Release 1.0.0
 */

require_once 'PHP/CodeCoverage.php';
require_once 'PHP/CodeCoverage/Report/HTML/Node.php';
require_once 'Text/Template.php';

/**
 * Generates an HTML report from an PHP_CodeCoverage object.
 *
 * @category   PHP
 * @package    CodeCoverage
 * @author     Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @copyright  2009-2011 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version    Release: @package_version@
 * @link       http://github.com/sebastianbergmann/php-code-coverage
 * @since      Class available since Release 1.0.0
 */
class PHP_CodeCoverage_Report_HTML
{
    /**
     * @var string
     */
    public static $templatePath;

    /**
     * @var array
     */
    protected $options;

    /**
     * Constructor.
     *
     * @param array $options
     */
    public function __construct(array $options = array())
    {
        if (!isset($options['title'])) {
            $options['title'] = '';
        }

        if (!isset($options['charset'])) {
            $options['charset'] = 'UTF-8';
        }

        if (!isset($options['yui'])) {
            $options['yui'] = TRUE;
        }

        if (!isset($options['highlight'])) {
            $options['highlight'] = FALSE;
        }

        if (!isset($options['lowUpperBound'])) {
            $options['lowUpperBound'] = 35;
        }

        if (!isset($options['highLowerBound'])) {
            $options['highLowerBound'] = 70;
        }

        if (!isset($options['generator'])) {
            $options['generator'] = '';
        }

        $this->options = $options;

        self::$templatePath = sprintf(
          '%s%sHTML%sTemplate%s',

          dirname(__FILE__),
          DIRECTORY_SEPARATOR,
          DIRECTORY_SEPARATOR,
          DIRECTORY_SEPARATOR
        );
    }

    /**
     * @param PHP_CodeCoverage $coverage
     * @param string           $target
     */
    public function process(PHP_CodeCoverage $coverage, $target)
    {
        $target     = PHP_CodeCoverage_Util::getDirectory($target);
        $files      = $coverage->getSummary();
        $commonPath = PHP_CodeCoverage_Util::reducePaths($files);
        $items      = PHP_CodeCoverage_Util::buildDirectoryStructure($files);
        $root       = new PHP_CodeCoverage_Report_HTML_Node_Directory(
                        $commonPath, NULL
                      );

        $this->addItems($root, $items);

        $this->renderDashboard(
          $root, $target . 'index.dashboard.html', $this->options['title']
        );

        foreach ($root as $node) {
            if ($node instanceof PHP_CodeCoverage_Report_HTML_Node_Directory) {
                $this->renderDashboard(
                  $node,
                  $target . PHP_CodeCoverage_Util::getSafeFilename(
                              $node->getId()
                            ) . '.dashboard.html',
                  $node->getName(TRUE)
                );
            }
        }

        $root->render(
          $target,
          $this->options['title'],
          $this->options['charset'],
          $this->options['lowUpperBound'],
          $this->options['highLowerBound'],
          $this->options['generator']
        );

        $this->copyFiles($target);
    }

    /**
     * @param PHP_CodeCoverage_Report_HTML_Node_Directory $root
     * @param string                                      $file
     * @param string                                      $title
     */
    protected function renderDashboard(PHP_CodeCoverage_Report_HTML_Node_Directory $root, $file, $title)
    {
        $classes  = $this->classes($root);
        $template = new Text_Template(
          PHP_CodeCoverage_Report_HTML::$templatePath . 'dashboard.html'
        );

        $template->setVar(
          array(
            'title'                  => $title,
            'charset'                => $this->options['charset'],
            'date'                   => date(
                                          'D M j G:i:s T Y',
                                          $_SERVER['REQUEST_TIME']
                                        ),
            'version'                => '@package_version@',
            'php_version'            => PHP_VERSION,
            'generator'              => $this->options['generator'],
            'least_tested_methods'   => $this->leastTestedMethods($classes),
            'top_project_risks'      => $this->topProjectRisks($classes),
            'cc_values'              => $this->classComplexity($classes),
            'ccd_values'             => $this->classCoverageDistribution($classes),
            'backlink'               => basename(str_replace('.dashboard', '', $file))
          )
        );

        $template->renderTo($file);
    }

    /**
     * @param PHP_CodeCoverage_Report_HTML_Node_Directory $root
     * @param array                                       $items
     */
    protected function addItems(PHP_CodeCoverage_Report_HTML_Node_Directory $root, array $items)
    {
        foreach ($items as $key => $value) {
            if (substr($key, -2) == '/f') {
                try {
                    $root->addFile(
                      substr($key, 0, -2),
                      $value,
                      $this->options['yui'],
                      $this->options['highlight']
                    );
                }

                catch (RuntimeException $e) {
                    continue;
                }
            } else {
                $child = $root->addDirectory($key);
                $this->addItems($child, $value);
            }
        }
    }

    /**
     * Returns the classes.
     *
     * @param  PHP_CodeCoverage_Report_HTML_Node_Directory $root
     * @return array
     */
    protected function classes(PHP_CodeCoverage_Report_HTML_Node_Directory $root)
    {
        $classes = array();

        foreach ($root as $node) {
            if ($node instanceof PHP_CodeCoverage_Report_HTML_Node_File) {
                $classes = array_merge($classes, $node->getClasses());
            }
        }

        if (isset($classes['*'])) {
            unset($classes['*']);
        }

        return $classes;
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
            $data[] = array($class['coverage'], $class['ccn'], 'blue', $name);
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
     * @param string $target
     */
    protected function copyFiles($target)
    {
        $files = array(
          'butter.png',
          'chameleon.png',
          'close12_1.gif',
          'container.css',
          'container-min.js',
          'directory.png',
          'excanvas.compressed.js',
          'file.png',
          'glass.png',
          'RGraph.bar.js',
          'RGraph.common.core.js',
          'RGraph.common.tooltips.js',
          'RGraph.scatter.js',
          'scarlet_red.png',
          'snow.png',
          'style.css',
          'yahoo-dom-event.js'
        );

        foreach ($files as $file) {
            copy(self::$templatePath . $file, $target . $file);
        }
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
              $classes[$class]['methods'][$method]['file'],
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

        asort($risks);

        $risks = array_reverse(
          array_slice($risks, 0, min($max, count($risks)))
        );

        $buffer = '';

        foreach ($risks as $name => $crap) {
            $buffer .= sprintf(
              '              <li><a href="%s">%s</a> (%d)</li>' . "\n",
              $classes[$name]['file'],
              $name,
              $crap
            );
        }

        return $buffer;
    }
}
