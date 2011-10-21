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
 * Generates human readable output from an PHP_CodeCoverage object.
 *
 * The output gets put into a text file our written to the CLI.
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
class PHP_CodeCoverage_Report_Text
{
    protected $outputStream;
    protected $title;
    protected $lowUpperBound;
    protected $highLowerBound;
    protected $showUncoveredFiles;

    protected $colors = array(
      'green'  => "\x1b[30;42m",
      'yellow' => "\x1b[30;43m",
      'red'    => "\x1b[37;41m",
      'header' => "\x1b[47;40m",
      'reset'  => "\x1b[0m",
      'eol'    => "\x1b[2K",
    );

    public function __construct(PHPUnit_Util_Printer $outputStream, $title, $lowUpperBound, $highLowerBound, $showUncoveredFiles)
    {
        $this->outputStream       = $outputStream;
        $this->title              = $title;
        $this->lowUpperBound      = $lowUpperBound;
        $this->highLowerBound     = $highLowerBound;
        $this->showUncoveredFiles = $showUncoveredFiles;
    }

    /**
     * @param  PHP_CodeCoverage $coverage
     * @param  string           $target
     * @param  string           $name
     * @return string
     */
    public function process(PHP_CodeCoverage $coverage, $showColors = FALSE)
    {
        $output   = '';
        $packages = array();
        $report   = $coverage->getReport();
        unset($coverage);

        $colors = array(
          'header'  => '',
          'classes' => '',
          'methods' => '',
          'lines'   => '',
          'reset'   => '',
          'eol'     => ''
        );

        if ($showColors) {
            $colors['classes'] = $this->getCoverageColor(
                                   $report->getNumTestedClasses(),
                                   $report->getNumClasses()
                                 );
            $colors['methods'] = $this->getCoverageColor(
                                   $report->getNumTestedMethods(),
                                   $report->getNumMethods()
                                 );
            $colors['lines']   = $this->getCoverageColor(
                                   $report->getNumExecutedLines(),
                                   $report->getNumExecutableLines()
                                 );
            $colors['reset']   = $this->colors['reset'];
            $colors['header']  = $this->colors['header'];
            $colors['eol']     = $this->colors['eol'];
        }

        $output .= PHP_EOL . PHP_EOL .
                   $colors['header'] . 'Code Coverage Report ';

        if ($this->title) {
            $output .= 'for "' . $this->title . '"';
        }

        $output .= PHP_EOL .
                   date('  Y-m-d H:i:s', $_SERVER['REQUEST_TIME']) .
                   PHP_EOL;

        $output .= PHP_EOL . ' Summary: ' . PHP_EOL . $colors['reset']
          . $colors['classes'] . $colors['eol'] . '  Classes: ' . PHP_CodeCoverage_Util::percent($report->getNumTestedClasses(), $report->getNumClasses(), TRUE)
          . ' (' . $report->getNumTestedClasses() . '/' . $report->getNumClasses() . ')' . PHP_EOL . $colors ['eol']
          . $colors['methods'] . $colors['eol'] . '  Methods: ' . PHP_CodeCoverage_Util::percent($report->getNumTestedMethods(), $report->getNumMethods(), TRUE)
          . ' (' . $report->getNumTestedMethods() . '/' . $report->getNumMethods() . ')' . PHP_EOL . $colors ['eol']
          . $colors['lines'] . $colors['eol'] . '  Lines:   ' . PHP_CodeCoverage_Util::percent($report->getNumExecutedLines(), $report->getNumExecutableLines(), TRUE)
          . ' (' . $report->getNumExecutedLines() . '/' . $report->getNumExecutableLines() . ')' . PHP_EOL . $colors['reset'] . $colors ['eol'];

        $classCoverage = array();

        foreach ($report as $item) {
            if (!$item instanceof PHP_CodeCoverage_Report_Node_File) {
                continue;
            }

            $classes      = array_merge($item->getClasses(), $item->getTraits());
            $coverage     = $item->getCoverageData();
            $lines        = array();
            $ignoredLines = $item->getIgnoredLines();

            foreach ($classes as $className => $class) {
                $classStatements        = 0;
                $coveredClassStatements = 0;
                $coveredMethods         = 0;

                foreach ($class['methods'] as $methodName => $method) {
                    $methodCount        = 0;
                    $methodLines        = 0;
                    $methodLinesCovered = 0;

                    for ($i  = $method['startLine'];
                         $i <= $method['endLine'];
                         $i++) {
                        if (isset($ignoredLines[$i])) {
                            continue;
                        }

                        $add   = TRUE;
                        $count = 0;

                        if (isset($coverage[$i])) {
                            if ($coverage[$i] !== NULL) {
                                $classStatements++;
                                $methodLines++;
                            } else {
                                $add = FALSE;
                            }

                            $count = count($coverage[$i]);

                            if ($count > 0) {
                                $coveredClassStatements++;
                                $methodLinesCovered++;
                            }
                        } else {
                            $add = FALSE;
                        }

                        $methodCount = max($methodCount, $count);

                        if ($add) {
                            $lines[$i] = array(
                              'count' => $count, 'type'  => 'stmt'
                            );
                        }
                    }

                    if ($methodCount > 0) {
                        $coveredMethods++;
                    }

                }

                if (!empty($class['package']['namespace'])) {
                    $namespace = '\\' . $class['package']['namespace'] . '::';
                }

                else if (!empty($class['package']['fullPackage'])) {
                    $namespace = '@' . $class['package']['fullPackage'] . '::';
                }

                else {
                    $namespace = '';
                }

                $classCoverage[$namespace . $className] = array(
                    'namespace'         => $namespace,
                    'className '        => $className,
                    'methodsCovered'    => $coveredMethods,
                    'methodCount'       => count($class['methods']),
                    'statementsCovered' => $coveredClassStatements,
                    'statementCount'    => $classStatements,
                );
            }
        }

        ksort($classCoverage);

        $methodColor = '';
        $linesColor  = '';
        $resetColor  = '';

        foreach ($classCoverage as $fullQualifiedPath => $classInfo) {
            if ($classInfo['statementsCovered'] != 0 ||
                $this->showUncoveredFiles) {

                if ($showColors) {
                    $methodColor = $this->getCoverageColor($classInfo['methodsCovered'], $classInfo['methodCount']);
                    $linesColor  = $this->getCoverageColor($classInfo['statementsCovered'], $classInfo['statementCount']);
                    $resetColor  = $colors['reset'];
                }

                $output .= PHP_EOL . $fullQualifiedPath . PHP_EOL
                  . '  ' . $methodColor . 'Methods: ' . $this->printCoverageCounts($classInfo['methodsCovered'], $classInfo['methodCount'], 2) . $resetColor . ' '
                  . '  ' . $linesColor  . 'Lines: ' . $this->printCoverageCounts($classInfo['statementsCovered'], $classInfo['statementCount'], 3) . $resetColor
                ;
            }
        }

        $this->outputStream->write($output);
    }

    protected function getCoverageColor($numberOfCoveredElements, $totalNumberOfElements)
    {
        $coverage = PHP_CodeCoverage_Util::percent(
          $numberOfCoveredElements, $totalNumberOfElements
        );

        if ($coverage > $this->highLowerBound) {
            return $this->colors['green'];
        }

        else if ($coverage > $this->lowUpperBound) {
            return $this->colors['yellow'];
        }

        return $this->colors['red'];
    }

    protected function printCoverageCounts($numberOfCoveredElements, $totalNumberOfElements, $presicion)
    {
        $format = '%' . $presicion . 's';

        return PHP_CodeCoverage_Util::percent(
          $numberOfCoveredElements, $totalNumberOfElements, TRUE, TRUE
        ) .
        ' (' . sprintf($format, $numberOfCoveredElements) . '/' .
        sprintf($format, $totalNumberOfElements) . ')';
    }
}
