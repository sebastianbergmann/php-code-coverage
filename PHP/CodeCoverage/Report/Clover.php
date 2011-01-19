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
require_once 'PHP/Token/Stream/CachingFactory.php';

/**
 * Generates a Clover XML logfile from an PHP_CodeCoverage object.
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
class PHP_CodeCoverage_Report_Clover
{
    /**
     * @param  PHP_CodeCoverage $coverage
     * @param  string           $target
     * @param  string           $name
     * @return string
     */
    public function process(PHP_CodeCoverage $coverage, $target = NULL, $name = NULL)
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $document->formatOutput = TRUE;

        $root = $document->createElement('coverage');
        $root->setAttribute('generated', (int)$_SERVER['REQUEST_TIME']);
        $document->appendChild($root);

        $project = $document->createElement('project');
        $project->setAttribute('timestamp', (int)$_SERVER['REQUEST_TIME']);

        if (is_string($name)) {
            $project->setAttribute('name', $name);
        }

        $root->appendChild($project);

        $files    = $coverage->getSummary();
        $packages = array();

        $projectStatistics = array(
          'files'               => 0,
          'loc'                 => 0,
          'ncloc'               => 0,
          'classes'             => 0,
          'methods'             => 0,
          'coveredMethods'      => 0,
          'conditionals'        => 0,
          'coveredConditionals' => 0,
          'statements'          => 0,
          'coveredStatements'   => 0
        );

        foreach ($files as $filename => $data) {
            $namespace = 'global';

            if (file_exists($filename)) {
                $fileStatistics = array(
                  'classes'             => 0,
                  'methods'             => 0,
                  'coveredMethods'      => 0,
                  'conditionals'        => 0,
                  'coveredConditionals' => 0,
                  'statements'          => 0,
                  'coveredStatements'   => 0
                );

                $file = $document->createElement('file');
                $file->setAttribute('name', $filename);

                $tokens        = PHP_Token_Stream_CachingFactory::get($filename);
                $classesInFile = $tokens->getClasses();
                $linesOfCode   = $tokens->getLinesOfCode();
                unset($tokens);

                $ignoredLines = PHP_CodeCoverage_Util::getLinesToBeIgnored(
                  $filename
                );

                $lines = array();

                foreach ($classesInFile as $className => $_class) {
                    $classStatistics = array(
                      'methods'             => 0,
                      'coveredMethods'      => 0,
                      'conditionals'        => 0,
                      'coveredConditionals' => 0,
                      'statements'          => 0,
                      'coveredStatements'   => 0
                    );

                    foreach ($_class['methods'] as $methodName => $method) {
                        $classStatistics['methods']++;

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

                            if (isset($files[$filename][$i])) {
                                if ($files[$filename][$i] != -2) {
                                    $classStatistics['statements']++;
                                    $methodLines++;
                                }

                                if (is_array($files[$filename][$i])) {
                                    $classStatistics['coveredStatements']++;
                                    $methodLinesCovered++;
                                    $count = count($files[$filename][$i]);
                                }

                                else if ($files[$filename][$i] == -2) {
                                    $add = FALSE;
                                }
                            } else {
                                $add = FALSE;
                            }

                            $methodCount = max($methodCount, $count);

                            if ($add) {
                                $lines[$i] = array(
                                  'count' => $count,
                                  'type'  => 'stmt'
                                );
                            }
                        }

                        if ($methodCount > 0) {
                            $classStatistics['coveredMethods']++;
                        }

                        $lines[$method['startLine']] = array(
                          'count' => $methodCount,
                          'crap'  => PHP_CodeCoverage_Util::crap(
                                       $method['ccn'],
                                       PHP_CodeCoverage_Util::percent(
                                         $methodLinesCovered,
                                         $methodLines
                                       )
                                     ),
                          'type'  => 'method',
                          'name'  => $methodName
                        );
                    }

                    $package = PHP_CodeCoverage_Util::getPackageInformation(
                      $className, $_class['docblock']
                    );

                    if (!empty($package['namespace'])) {
                        $namespace = $package['namespace'];
                    }

                    $class = $document->createElement('class');
                    $class->setAttribute('name', $className);
                    $class->setAttribute('namespace', $namespace);

                    if (!empty($package['fullPackage'])) {
                        $class->setAttribute(
                          'fullPackage', $package['fullPackage']
                        );
                    }

                    if (!empty($package['category'])) {
                        $class->setAttribute(
                          'category', $package['category']
                        );
                    }

                    if (!empty($package['package'])) {
                        $class->setAttribute(
                          'package', $package['package']
                        );
                    }

                    if (!empty($package['subpackage'])) {
                        $class->setAttribute(
                          'subpackage', $package['subpackage']
                        );
                    }

                    $file->appendChild($class);

                    $metrics = $document->createElement('metrics');

                    $metrics->setAttribute(
                      'methods', $classStatistics['methods']
                    );

                    $metrics->setAttribute(
                      'coveredmethods', $classStatistics['coveredMethods']
                    );

                    $metrics->setAttribute(
                      'conditionals', $classStatistics['conditionals']
                    );

                    $metrics->setAttribute(
                      'coveredconditionals',
                      $classStatistics['coveredConditionals']
                    );

                    $metrics->setAttribute(
                      'statements', $classStatistics['statements']
                    );

                    $metrics->setAttribute(
                      'coveredstatements',
                      $classStatistics['coveredStatements']
                    );

                    $metrics->setAttribute(
                      'elements',
                      $classStatistics['conditionals'] +
                      $classStatistics['statements']   +
                      $classStatistics['methods']
                    );

                    $metrics->setAttribute(
                      'coveredelements',
                      $classStatistics['coveredConditionals'] +
                      $classStatistics['coveredStatements']   +
                      $classStatistics['coveredMethods']
                    );

                    $class->appendChild($metrics);

                    $fileStatistics['methods']             += $classStatistics['methods'];
                    $fileStatistics['coveredMethods']      += $classStatistics['coveredMethods'];
                    $fileStatistics['conditionals']        += $classStatistics['conditionals'];
                    $fileStatistics['coveredConditionals'] += $classStatistics['coveredConditionals'];
                    $fileStatistics['statements']          += $classStatistics['statements'];
                    $fileStatistics['coveredStatements']   += $classStatistics['coveredStatements'];
                    $fileStatistics['classes']++;
                }

                foreach ($data as $_line => $_data) {
                    if (isset($lines[$_line]) || isset($ignoredLines[$_line])) {
                        continue;
                    }

                    if ($_data != -2) {
                        $fileStatistics['statements']++;

                        if (is_array($_data)) {
                            $count = count($_data);
                            $fileStatistics['coveredStatements']++;
                        } else {
                            $count = 0;
                        }

                        $lines[$_line] = array(
                          'count' => $count,
                          'type' => 'stmt'
                        );
                    }
                }

                ksort($lines);

                foreach ($lines as $_line => $_data) {
                    if (isset($ignoredLines[$_line])) {
                        continue;
                    }

                    $line = $document->createElement('line');
                    $line->setAttribute('num', $_line);
                    $line->setAttribute('type', $_data['type']);

                    if (isset($_data['name'])) {
                        $line->setAttribute('name', $_data['name']);
                    }

                    if (isset($_data['crap'])) {
                        $line->setAttribute('crap', $_data['crap']);
                    }

                    $line->setAttribute('count', $_data['count']);

                    $file->appendChild($line);
                }

                $metrics = $document->createElement('metrics');

                $metrics->setAttribute('loc', $linesOfCode['loc']);
                $metrics->setAttribute('ncloc', $linesOfCode['ncloc']);
                $metrics->setAttribute('classes', $fileStatistics['classes']);
                $metrics->setAttribute('methods', $fileStatistics['methods']);

                $metrics->setAttribute(
                  'coveredmethods', $fileStatistics['coveredMethods']
                );

                $metrics->setAttribute(
                  'conditionals', $fileStatistics['conditionals']
                );

                $metrics->setAttribute(
                  'coveredconditionals', $fileStatistics['coveredConditionals']
                );

                $metrics->setAttribute(
                  'statements', $fileStatistics['statements']
                );

                $metrics->setAttribute(
                  'coveredstatements', $fileStatistics['coveredStatements']
                );

                $metrics->setAttribute(
                  'elements',
                  $fileStatistics['conditionals'] +
                  $fileStatistics['statements']   +
                  $fileStatistics['methods']
                );

                $metrics->setAttribute(
                  'coveredelements',
                  $fileStatistics['coveredConditionals'] +
                  $fileStatistics['coveredStatements']   +
                  $fileStatistics['coveredMethods']
                );

                $file->appendChild($metrics);

                if ($namespace == 'global') {
                    $project->appendChild($file);
                } else {
                    if (!isset($packages[$namespace])) {
                        $packages[$namespace] = $document->createElement(
                          'package'
                        );

                        $packages[$namespace]->setAttribute('name', $namespace);
                        $project->appendChild($packages[$namespace]);
                    }

                    $packages[$namespace]->appendChild($file);
                }

                $projectStatistics['loc']                 += $linesOfCode['loc'];
                $projectStatistics['ncloc']               += $linesOfCode['ncloc'];
                $projectStatistics['classes']             += $fileStatistics['classes'];
                $projectStatistics['methods']             += $fileStatistics['methods'];
                $projectStatistics['coveredMethods']      += $fileStatistics['coveredMethods'];
                $projectStatistics['conditionals']        += $fileStatistics['conditionals'];
                $projectStatistics['coveredConditionals'] += $fileStatistics['coveredConditionals'];
                $projectStatistics['statements']          += $fileStatistics['statements'];
                $projectStatistics['coveredStatements']   += $fileStatistics['coveredStatements'];
                $projectStatistics['files']++;
            }
        }

        $metrics = $document->createElement('metrics');

        $metrics->setAttribute('files', $projectStatistics['files']);
        $metrics->setAttribute('loc', $projectStatistics['loc']);
        $metrics->setAttribute('ncloc', $projectStatistics['ncloc']);
        $metrics->setAttribute('classes', $projectStatistics['classes']);
        $metrics->setAttribute('methods', $projectStatistics['methods']);

        $metrics->setAttribute(
          'coveredmethods', $projectStatistics['coveredMethods']
        );

        $metrics->setAttribute(
          'conditionals', $projectStatistics['conditionals']
        );

        $metrics->setAttribute(
          'coveredconditionals', $projectStatistics['coveredConditionals']
        );

        $metrics->setAttribute(
          'statements', $projectStatistics['statements']
        );

        $metrics->setAttribute(
          'coveredstatements', $projectStatistics['coveredStatements']
        );

        $metrics->setAttribute(
          'elements',
          $projectStatistics['conditionals'] +
          $projectStatistics['statements']   +
          $projectStatistics['methods']
        );

        $metrics->setAttribute(
          'coveredelements',
          $projectStatistics['coveredConditionals'] +
          $projectStatistics['coveredStatements']   +
          $projectStatistics['coveredMethods']
        );

        $project->appendChild($metrics);

        if ($target !== NULL) {
            if (!is_dir(dirname($target))) {
              mkdir(dirname($target), 0777, TRUE);
            }

            return $document->save($target);
        } else {
            return $document->saveXML();
        }
    }
}
