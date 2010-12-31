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

if (!defined('T_NAMESPACE')) {
    define('T_NAMESPACE', 377);
}

require_once 'PHP/Token/Stream/CachingFactory.php';

/**
 * Represents a file in the code coverage information tree.
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
class PHP_CodeCoverage_Report_HTML_Node_File extends PHP_CodeCoverage_Report_HTML_Node
{
    /**
     * @var array
     */
    protected $codeLines;

    /**
     * @var array
     */
    protected $codeLinesFillup = array();

    /**
     * @var array
     */
    protected $executedLines;

    /**
     * @var boolean
     */
    protected $yui = TRUE;

    /**
     * @var boolean
     */
    protected $highlight = FALSE;

    /**
     * @var integer
     */
    protected $numExecutableLines = 0;

    /**
     * @var integer
     */
    protected $numExecutedLines = 0;

    /**
     * @var array
     */
    protected $classes = array();

    /**
     * @var integer
     */
    protected $numTestedClasses = 0;

    /**
     * @var integer
     */
    protected $numClasses = NULL;

    /**
     * @var integer
     */
    protected $numMethods = NULL;

    /**
     * @var integer
     */
    protected $numTestedMethods = NULL;

    /**
     * @var string
     */
    protected $yuiPanelJS = '';

    /**
     * @var array
     */
    protected $startLines = array();

    /**
     * @var array
     */
    protected $endLines = array();

    /**
     * Constructor.
     *
     * @param  string                            $name
     * @param  PHP_CodeCoverage_Report_HTML_Node $parent
     * @param  array                             $executedLines
     * @param  boolean                           $yui
     * @param  boolean                           $highlight
     * @throws RuntimeException
     */
    public function __construct($name, PHP_CodeCoverage_Report_HTML_Node $parent, array $executedLines, $yui = TRUE, $highlight = FALSE)
    {
        parent::__construct($name, $parent);

        $path = $this->getPath();

        if (!file_exists($path)) {
            throw new RuntimeException(
              sprintf('Path "%s" does not exist.', $path)
            );
        }

        $this->executedLines = $executedLines;
        $this->highlight     = $highlight;
        $this->yui           = $yui;
        $this->codeLines     = $this->loadFile($path);
        $this->ignoredLines  = PHP_CodeCoverage_Util::getLinesToBeIgnored(
                                 $path
                               );

        $this->calculateStatistics();
    }

    /**
     * Returns the classes of this node.
     *
     * @return array
     */
    public function getClasses()
    {
        return $this->classes;
    }

    /**
     * Returns the number of executable lines.
     *
     * @return integer
     */
    public function getNumExecutableLines()
    {
        return $this->numExecutableLines;
    }

    /**
     * Returns the number of executed lines.
     *
     * @return integer
     */
    public function getNumExecutedLines()
    {
        return $this->numExecutedLines;
    }

    /**
     * Returns the number of classes.
     *
     * @return integer
     */
    public function getNumClasses()
    {
        if ($this->numClasses === NULL) {
            $this->numClasses = count($this->classes);

            if (isset($this->classes['*'])) {
                $this->numClasses--;
            }
        }

        return $this->numClasses;
    }

    /**
     * Returns the number of tested classes.
     *
     * @return integer
     */
    public function getNumTestedClasses()
    {
        return $this->numTestedClasses;
    }

    /**
     * Returns the number of methods.
     *
     * @return integer
     */
    public function getNumMethods()
    {
        if ($this->numMethods === NULL) {
            $this->numMethods = 0;

            foreach ($this->classes as $class) {
                foreach ($class['methods'] as $method) {
                    if ($method['executableLines'] > 0) {
                        $this->numMethods++;
                    }
                }
            }
        }

        return $this->numMethods;
    }

    /**
     * Returns the number of tested methods.
     *
     * @return integer
     */
    public function getNumTestedMethods()
    {
        if ($this->numTestedMethods === NULL) {
            $this->numTestedMethods = 0;

            foreach ($this->classes as $class) {
                foreach ($class['methods'] as $method) {
                    if ($method['executableLines'] > 0 &&
                        $method['coverage'] == 100) {
                        $this->numTestedMethods++;
                    }
                }
            }
        }

        return $this->numTestedMethods;
    }

    /**
     * Renders this node.
     *
     * @param string  $target
     * @param string  $title
     * @param string  $charset
     * @param integer $lowUpperBound
     * @param integer $highLowerBound
     * @param string  $generator
     */
    public function render($target, $title, $charset = 'UTF-8', $lowUpperBound = 35, $highLowerBound = 70, $generator = '')
    {
        if ($this->yui) {
            $template = new Text_Template(
              PHP_CodeCoverage_Report_HTML::$templatePath . 'file.html'
            );

            $yuiTemplate = new Text_Template(
              PHP_CodeCoverage_Report_HTML::$templatePath . 'yui_item.js'
            );
        } else {
            $template = new Text_Template(
              PHP_CodeCoverage_Report_HTML::$templatePath . 'file_no_yui.html'
            );
        }

        $i     = 1;
        $lines = '';

        foreach ($this->codeLines as $line) {
            $css = '';

            if (!isset($this->ignoredLines[$i]) &&
                 isset($this->executedLines[$i])) {
                $count = '';

                // Array: Line is executable and was executed.
                // count(Array) = Number of tests that hit this line.
                if (is_array($this->executedLines[$i])) {
                    $color    = 'lineCov';
                    $numTests = count($this->executedLines[$i]);
                    $count    = sprintf('%8d', $numTests);

                    if ($this->yui) {
                        $buffer  = '';
                        $testCSS = '';

                        foreach ($this->executedLines[$i] as $test) {
                            switch ($test['status']) {
                                case 0: {
                                    $testCSS = ' class=\"testPassed\"';
                                }
                                break;

                                case 1:
                                case 2: {
                                    $testCSS = ' class=\"testIncomplete\"';
                                }
                                break;

                                case 3: {
                                    $testCSS = ' class=\"testFailure\"';
                                }
                                break;

                                case 4: {
                                    $testCSS = ' class=\"testError\"';
                                }
                                break;

                                default: {
                                    $testCSS = '';
                                }
                            }

                            $buffer .= sprintf(
                              '<li%s>%s</li>',

                              $testCSS,
                              addslashes(htmlspecialchars($test['id']))
                            );
                        }

                        if ($numTests > 1) {
                            $header = $numTests . ' tests cover';
                        } else {
                            $header = '1 test covers';
                        }

                        $header .= ' line ' . $i;

                        $yuiTemplate->setVar(
                          array(
                            'line'   => $i,
                            'header' => $header,
                            'tests'  => $buffer
                          ),
                          FALSE
                        );

                        $this->yuiPanelJS .= $yuiTemplate->render();
                    }
                }

                // -1: Line is executable and was not executed.
                else if ($this->executedLines[$i] == -1) {
                    $color = 'lineNoCov';
                    $count = sprintf('%8d', 0);
                }

                // -2: Line is dead code.
                else {
                    $color = 'lineDeadCode';
                    $count = '        ';
                }

                $css = sprintf(
                  '<span class="%s">       %s : ',

                  $color,
                  $count
                );
            }

            $fillup = array_shift($this->codeLinesFillup);

            if ($fillup > 0) {
                $line .= str_repeat(' ', $fillup);
            }

            $lines .= sprintf(
              '<span class="lineNum" id="container%d"><a name="%d"></a>'.
              '<a href="#%d" id="line%d">%8d</a> </span>%s%s%s' . "\n",

              $i,
              $i,
              $i,
              $i,
              $i,
              !empty($css) ? $css : '                : ',
              !$this->highlight ? htmlspecialchars($line) : $line,
              !empty($css) ? '</span>' : ''
            );

            $i++;
        }

        $items = '';

        foreach ($this->classes as $className => $classData) {
            if ($classData['executedLines'] == $classData['executableLines']) {
                $numTestedClasses     = 1;
                $testedClassesPercent = 100;
            } else {
                $numTestedClasses     = 0;
                $testedClassesPercent = 0;
            }

            $numMethods       = 0;
            $numTestedMethods = 0;

            foreach ($classData['methods'] as $method) {
                if ($method['executableLines'] > 0) {
                    $numMethods++;

                    if ($method['executedLines'] == $method['executableLines']) {
                        $numTestedMethods++;
                    }
                }
            }

            $items .= $this->doRenderItem(
              array(
                'name'                 => sprintf(
                  '<b><a href="#%d">%s</a></b>',

                  $classData['startLine'],
                  $className
                ),
                'numClasses'           => 1,
                'numTestedClasses'     => $numTestedClasses,
                'testedClassesPercent' => sprintf(
                                            '%01.2f', $testedClassesPercent
                                          ),
                'numMethods'           => $numMethods,
                'numTestedMethods'     => $numTestedMethods,
                'testedMethodsPercent' => PHP_CodeCoverage_Util::percent(
                  $numTestedMethods, $numMethods, TRUE
                ),
                'numExecutableLines'   => $classData['executableLines'],
                'numExecutedLines'     => $classData['executedLines'],
                'executedLinesPercent' => PHP_CodeCoverage_Util::percent(
                  $classData['executedLines'],
                  $classData['executableLines'],
                  TRUE
                )
              ),
              $lowUpperBound,
              $highLowerBound
            );

            foreach ($classData['methods'] as $methodData) {
                if ($methodData['executableLines'] > 0) {
                    if ($methodData['executedLines'] == $methodData['executableLines']) {
                        $numTestedMethods     = 1;
                        $testedMethodsPercent = 100;
                    } else {
                        $numTestedMethods     = 0;
                        $testedMethodsPercent = 0;
                    }

                    $items .= $this->doRenderItem(
                      array(
                        'name'                 => sprintf(
                          '&nbsp;<a href="#%d">%s</a>',

                          $methodData['startLine'],
                          htmlspecialchars($methodData['signature'])
                        ),
                        'numClasses'           => '',
                        'numTestedClasses'     => '',
                        'testedClassesPercent' => '',
                        'numMethods'           => 1,
                        'numTestedMethods'     => $numTestedMethods,
                        'testedMethodsPercent' => sprintf(
                                                    '%01.2f', $testedMethodsPercent
                                                  ),
                        'numExecutableLines'   => $methodData['executableLines'],
                        'numExecutedLines'     => $methodData['executedLines'],
                        'executedLinesPercent' => PHP_CodeCoverage_Util::percent(
                          $methodData['executedLines'],
                          $methodData['executableLines'],
                          TRUE
                        ),
                        'crap'                 => PHP_CodeCoverage_Util::crap(
                                                    $methodData['ccn'],
                                                    PHP_CodeCoverage_Util::percent(
                                                      $methodData['executedLines'],
                                                      $methodData['executableLines']
                                                    )
                                                  )
                      ),
                      $lowUpperBound,
                      $highLowerBound,
                      'method_item.html'
                    );
                }
            }
        }

        $this->setTemplateVars($template, $title, $charset, $generator);

        $template->setVar(
          array(
            'lines'      => $lines,
            'total_item' => $this->renderTotalItem(
                              $lowUpperBound, $highLowerBound, FALSE
                            ),
            'items'      => $items,
            'yuiPanelJS' => $this->yuiPanelJS
          )
        );

        $cleanId = PHP_CodeCoverage_Util::getSafeFilename($this->getId());
        $template->renderTo($target . $cleanId . '.html');

        $this->yuiPanelJS    = '';
        $this->executedLines = array();
    }

    /**
     * Calculates coverage statistics for the file.
     *
     */
    protected function calculateStatistics()
    {
        $this->processClasses();
        $this->processFunctions();

        $max = count($this->codeLines);

        for ($lineNumber = 1; $lineNumber <= $max; $lineNumber++) {
            if (isset($this->startLines[$lineNumber])) {
                // Start line of a class.
                if (isset($this->startLines[$lineNumber]['methods'])) {
                    $currentClass = &$this->startLines[$lineNumber];
                }

                // Start line of a method.
                else {
                    $currentMethod = &$this->startLines[$lineNumber];
                }
            }

            if (isset($this->executedLines[$lineNumber])) {
                // Array: Line is executable and was executed.
                if (is_array($this->executedLines[$lineNumber])) {
                    if (isset($currentClass)) {
                        $currentClass['executableLines']++;
                        $currentClass['executedLines']++;
                    }

                    if (isset($currentMethod)) {
                        $currentMethod['executableLines']++;
                        $currentMethod['executedLines']++;
                    }

                    $this->numExecutableLines++;
                    $this->numExecutedLines++;
                }

                // -1: Line is executable and was not executed.
                else if ($this->executedLines[$lineNumber] == -1) {
                    if (isset($currentClass)) {
                        $currentClass['executableLines']++;
                    }

                    if (isset($currentMethod)) {
                        $currentMethod['executableLines']++;
                    }

                    $this->numExecutableLines++;

                    if (isset($this->ignoredLines[$lineNumber])) {
                        if (isset($currentClass)) {
                            $currentClass['executedLines']++;
                        }

                        if (isset($currentMethod)) {
                            $currentMethod['executedLines']++;
                        }

                        $this->numExecutedLines++;
                    }
                }
            }

            if (isset($this->endLines[$lineNumber])) {
                // End line of a class.
                if (isset($this->endLines[$lineNumber]['methods'])) {
                    unset($currentClass);
                }

                // End line of a method.
                else {
                    unset($currentMethod);
                }
            }
        }

        foreach ($this->classes as $className => &$class) {
            foreach ($class['methods'] as &$method) {
                if ($method['executableLines'] > 0) {
                    $method['coverage'] = ($method['executedLines'] /
                                           $method['executableLines']) * 100;
                } else {
                    $method['coverage'] = 100;
                }

                $method['crap'] = PHP_CodeCoverage_Util::crap(
                  $method['ccn'], $method['coverage']
                );

                $class['ccn'] += $method['ccn'];
            }

            if ($className != '*') {
                if ($class['executableLines'] > 0) {
                    $class['coverage'] = ($class['executedLines'] /
                                          $class['executableLines']) * 100;
                } else {
                    $class['coverage'] = 100;
                }

                if ($class['coverage'] == 100) {
                    $this->numTestedClasses++;
                }

                $class['crap'] = PHP_CodeCoverage_Util::crap(
                  $class['ccn'], $class['coverage']
                );
            }
        }
    }

    /**
     * @param  string $file
     * @return array
     * @author Aidan Lister <aidan@php.net>
     */
    protected function loadFile($file)
    {
        $buffer = file_get_contents($file);
        $lines  = explode("\n", str_replace("\t", '    ', $buffer));
        $result = array();

        if (count($lines) == 0) {
            return $result;
        }

        $lines       = array_map('rtrim', $lines);
        $linesLength = array_map('strlen', $lines);
        $width       = max($linesLength);

        foreach ($linesLength as $line => $length) {
            $this->codeLinesFillup[$line] = $width - $length;
        }

        if (!$this->highlight) {
            unset($lines[count($lines)-1]);
            return $lines;
        }

        $tokens     = token_get_all($buffer);
        $stringFlag = FALSE;
        $i          = 0;
        $result[$i] = '';

        foreach ($tokens as $j => $token) {
            if (is_string($token)) {
                if ($token === '"' && $tokens[$j - 1] !== '\\') {
                    $result[$i] .= sprintf(
                      '<span class="string">%s</span>',

                      htmlspecialchars($token)
                    );

                    $stringFlag = !$stringFlag;
                } else {
                    $result[$i] .= sprintf(
                      '<span class="keyword">%s</span>',

                      htmlspecialchars($token)
                    );
                }

                continue;
            }

            list ($token, $value) = $token;

            $value = str_replace(
              array("\t", ' '),
              array('&nbsp;&nbsp;&nbsp;&nbsp;', '&nbsp;'),
              htmlspecialchars($value)
            );

            if ($value === "\n") {
                $result[++$i] = '';
            } else {
                $lines = explode("\n", $value);

                foreach ($lines as $jj => $line) {
                    $line = trim($line);

                    if ($line !== '') {
                        if ($stringFlag) {
                            $colour = 'string';
                        } else {
                            switch ($token) {
                                case T_INLINE_HTML: {
                                    $colour = 'html';
                                }
                                break;

                                case T_COMMENT:
                                case T_DOC_COMMENT: {
                                    $colour = 'comment';
                                }
                                break;

                                case T_ABSTRACT:
                                case T_ARRAY:
                                case T_ARRAY_CAST:
                                case T_AS:
                                case T_BOOLEAN_AND:
                                case T_BOOLEAN_OR:
                                case T_BOOL_CAST:
                                case T_BREAK:
                                case T_CASE:
                                case T_CATCH:
                                case T_CLASS:
                                case T_CLONE:
                                case T_CONCAT_EQUAL:
                                case T_CONTINUE:
                                case T_DEFAULT:
                                case T_DOUBLE_ARROW:
                                case T_DOUBLE_CAST:
                                case T_ECHO:
                                case T_ELSE:
                                case T_ELSEIF:
                                case T_EMPTY:
                                case T_ENDDECLARE:
                                case T_ENDFOR:
                                case T_ENDFOREACH:
                                case T_ENDIF:
                                case T_ENDSWITCH:
                                case T_ENDWHILE:
                                case T_END_HEREDOC:
                                case T_EXIT:
                                case T_EXTENDS:
                                case T_FINAL:
                                case T_FOREACH:
                                case T_FUNCTION:
                                case T_GLOBAL:
                                case T_IF:
                                case T_INC:
                                case T_INCLUDE:
                                case T_INCLUDE_ONCE:
                                case T_INSTANCEOF:
                                case T_INT_CAST:
                                case T_ISSET:
                                case T_IS_EQUAL:
                                case T_IS_IDENTICAL:
                                case T_IS_NOT_IDENTICAL:
                                case T_IS_SMALLER_OR_EQUAL:
                                case T_NAMESPACE:
                                case T_NEW:
                                case T_OBJECT_CAST:
                                case T_OBJECT_OPERATOR:
                                case T_PAAMAYIM_NEKUDOTAYIM:
                                case T_PRIVATE:
                                case T_PROTECTED:
                                case T_PUBLIC:
                                case T_REQUIRE:
                                case T_REQUIRE_ONCE:
                                case T_RETURN:
                                case T_SL:
                                case T_SL_EQUAL:
                                case T_SR:
                                case T_SR_EQUAL:
                                case T_START_HEREDOC:
                                case T_STATIC:
                                case T_STRING_CAST:
                                case T_THROW:
                                case T_TRY:
                                case T_UNSET_CAST:
                                case T_USE:
                                case T_VAR:
                                case T_WHILE: {
                                    $colour = 'keyword';
                                }
                                break;

                                default: {
                                    $colour = 'default';
                                }
                            }
                        }

                        $result[$i] .= sprintf(
                          '<span class="%s">%s</span>',

                          $colour,
                          $line
                        );
                    }

                    if (isset($lines[$jj + 1])) {
                        $result[++$i] = '';
                    }
                }
            }
        }

        unset($result[count($result)-1]);

        return $result;
    }

    protected function processClasses()
    {
        $file    = $this->getId() . '.html#';
        $tokens  = PHP_Token_Stream_CachingFactory::get($this->getPath());
        $classes = $tokens->getClasses();
        unset($tokens);

        foreach ($classes as $className => $class) {
            $this->classes[$className] = array(
              'methods'         => array(),
              'startLine'       => $class['startLine'],
              'executableLines' => 0,
              'executedLines'   => 0,
              'ccn'             => 0,
              'coverage'        => 0,
              'crap'            => 0,
              'file'            => $file . $class['startLine']
            );

            $this->startLines[$class['startLine']] = &$this->classes[$className];
            $this->endLines[$class['endLine']]     = &$this->classes[$className];

            foreach ($class['methods'] as $methodName => $method) {
                $this->classes[$className]['methods'][$methodName] = array(
                  'signature'       => $method['signature'],
                  'startLine'       => $method['startLine'],
                  'executableLines' => 0,
                  'executedLines'   => 0,
                  'ccn'             => $method['ccn'],
                  'coverage'        => 0,
                  'crap'            => 0,
                  'file'            => $file . $method['startLine']
                );

                $this->startLines[$method['startLine']] = &$this->classes[$className]['methods'][$methodName];
                $this->endLines[$method['endLine']]     = &$this->classes[$className]['methods'][$methodName];
            }
        }
    }

    protected function processFunctions()
    {
        $tokens    = PHP_Token_Stream_CachingFactory::get($this->getPath());
        $functions = $tokens->getFunctions();
        unset($tokens);

        if (count($functions) > 0 && !isset($this->classes['*'])) {
            $this->classes['*'] = array(
              'methods'         => array(),
              'startLine'       => 0,
              'executableLines' => 0,
              'executedLines'   => 0,
              'ccn'             => 0
            );
        }

        foreach ($functions as $functionName => $function) {
            $this->classes['*']['methods'][$functionName] = array(
              'signature'       => $function['signature'],
              'startLine'       => $function['startLine'],
              'executableLines' => 0,
              'executedLines'   => 0,
              'ccn'             => $function['ccn']
            );

            $this->startLines[$function['startLine']] = &$this->classes['*']['methods'][$functionName];
            $this->endLines[$function['endLine']]     = &$this->classes['*']['methods'][$functionName];
        }
    }
}
