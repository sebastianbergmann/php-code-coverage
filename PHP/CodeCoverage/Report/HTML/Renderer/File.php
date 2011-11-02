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

if (!defined('T_NAMESPACE')) {
    define('T_NAMESPACE', 1000);
}

if (!defined('T_TRAIT')) {
    define('T_TRAIT', 1001);
}

/**
 * Renders a PHP_CodeCoverage_Report_Node_File node.
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
class PHP_CodeCoverage_Report_HTML_Renderer_File extends PHP_CodeCoverage_Report_HTML_Renderer
{
    /**
     * @var boolean
     */
    protected $highlight;

    /**
     * @var boolean
     */
    protected $yui;

    /**
     * Constructor.
     *
     * @param string  $templatePath
     * @param string  $charset
     * @param string  $generator
     * @param string  $date
     * @param integer $lowUpperBound
     * @param integer $highLowerBound
     * @param boolean $highlight
     * @param boolean $yui
     */
    public function __construct($templatePath, $charset, $generator, $date, $lowUpperBound, $highLowerBound, $highlight, $yui)
    {
        parent::__construct(
          $templatePath,
          $charset,
          $generator,
          $date,
          $lowUpperBound,
          $highLowerBound
        );

        $this->highlight = $highlight;
        $this->yui       = $yui;
    }

    /**
     * @param PHP_CodeCoverage_Report_Node_File $node
     * @param string                            $file
     * @param string                            $title
     */
    public function render(PHP_CodeCoverage_Report_Node_File $node, $file, $title = NULL)
    {
        if ($title === NULL) {
            $title = $node->getName();
        }

        if ($this->yui) {
            $template = new Text_Template($this->templatePath . 'file.html');
        } else {
            $template = new Text_Template(
              $this->templatePath . 'file_no_yui.html'
            );
        }

        list($source, $yuiTemplate) = $this->renderSource($node);

        $template->setVar(
          array(
            'items'      => $this->renderItems($node),
            'source'     => $source,
            'yuiPanelJS' => $yuiTemplate
          )
        );

        $this->setCommonTemplateVariables($template, $title, $node);

        $template->renderTo($file);
    }

    /**
     * @param  PHP_CodeCoverage_Report_Node_File $node
     * @return string
     */
    protected function renderItems(PHP_CodeCoverage_Report_Node_File $node)
    {
        $template = new Text_Template($this->templatePath . 'file_item.html');

        $methodItemTemplate = new Text_Template(
          $this->templatePath . 'method_item.html'
        );

        $items = $this->renderItemTemplate(
          $template,
          array(
            'itemClass'                    => 'coverDirectory',
            'name'                         => 'Total',
            'numClasses'                   => $node->getNumClasses() + $node->getNumTraits(),
            'numTestedClasses'             => $node->getNumTestedClasses() + $node->getNumTestedTraits(),
            'numMethods'                   => $node->getNumMethods(),
            'numTestedMethods'             => $node->getNumTestedMethods(),
            'linesExecutedPercent'         => $node->getLineExecutedPercent(FALSE),
            'linesExecutedPercentAsString' => $node->getLineExecutedPercent(),
            'numExecutedLines'             => $node->getNumExecutedLines(),
            'numExecutableLines'           => $node->getNumExecutableLines(),
            'testedMethodsPercent'         => $node->getTestedMethodsPercent(FALSE),
            'testedMethodsPercentAsString' => $node->getTestedMethodsPercent(),
            'testedClassesPercent'         => $node->getTestedClassesPercent(FALSE),
            'testedClassesPercentAsString' => $node->getTestedClassesPercent(),
            'crap'                         => '<acronym title="Change Risk Anti-Patterns (CRAP) Index">CRAP</acronym>'
          )
        );

        $items .= $this->renderFunctionItems(
          $node->getFunctions(), $methodItemTemplate
        );

        $items .= $this->renderTraitOrClassItems(
          $node->getTraits(), $template, $methodItemTemplate
        );

        $items .= $this->renderTraitOrClassItems(
          $node->getClasses(), $template, $methodItemTemplate
        );

        return $items;
    }

    /**
     * @param  array         $items
     * @param  Text_Template $template
     * @return string
     */
    protected function renderTraitOrClassItems(array $items, Text_Template $template, Text_Template $methodItemTemplate)
    {
        if (empty($items)) {
            return '';
        }

        $buffer = '';

        foreach ($items as $name => $item) {
            $numMethods       = count($item['methods']);
            $numTestedMethods = 0;

            foreach ($item['methods'] as $method) {
                if ($method['executedLines'] == $method['executableLines']) {
                    $numTestedMethods++;
                }
            }

            $buffer .= $this->renderItemTemplate(
              $template,
              array(
                'itemClass'                    => 'coverDirectory',
                'name'                         => $name,
                'numClasses'                   => 1,
                'numTestedClasses'             => $numTestedMethods == $numMethods ? 1 : 0,
                'numMethods'                   => $numMethods,
                'numTestedMethods'             => $numTestedMethods,
                'linesExecutedPercent'         => PHP_CodeCoverage_Util::percent(
                                                    $item['executedLines'],
                                                    $item['executableLines'],
                                                    FALSE
                                                  ),
                'linesExecutedPercentAsString' => PHP_CodeCoverage_Util::percent(
                                                    $item['executedLines'],
                                                    $item['executableLines'],
                                                    TRUE
                                                  ),
                'numExecutedLines'             => $item['executedLines'],
                'numExecutableLines'           => $item['executableLines'],
                'testedMethodsPercent'         => PHP_CodeCoverage_Util::percent(
                                                    $numTestedMethods,
                                                    $numMethods,
                                                    FALSE
                                                  ),
                'testedMethodsPercentAsString' => PHP_CodeCoverage_Util::percent(
                                                    $numTestedMethods,
                                                    $numMethods,
                                                    TRUE
                                                  ),
                'testedClassesPercent'         => PHP_CodeCoverage_Util::percent(
                                                    $numTestedMethods == $numMethods ? 1 : 0,
                                                    1,
                                                    FALSE
                                                  ),
                'testedClassesPercentAsString' => PHP_CodeCoverage_Util::percent(
                                                    $numTestedMethods == $numMethods ? 1 : 0,
                                                    1,
                                                    TRUE
                                                  ),
                'crap'                         => $item['crap']
              )
            );

            foreach ($item['methods'] as $method) {
                $buffer .= $this->renderFunctionOrMethodItem(
                  $methodItemTemplate, $method, '&nbsp;'
                );
            }
        }

        return $buffer;
    }

    /**
     * @param  array         $functions
     * @param  Text_Template $template
     * @return string
     */
    protected function renderFunctionItems(array $functions, Text_Template $template)
    {
        if (empty($functions)) {
            return '';
        }

        $buffer = '';

        foreach ($functions as $function) {
            $buffer .= $this->renderFunctionOrMethodItem(
              $template, $function
            );
        }

        return $buffer;
    }

    /**
     * @param  Text_Template $template
     * @return string
     */
    protected function renderFunctionOrMethodItem(Text_Template $template, array $item, $indent = '')
    {
        $numTestedItems = $item['executedLines'] == $item['executableLines'] ? 1 : 0;

        return $this->renderItemTemplate(
          $template,
          array(
            'name'                         => sprintf(
                                                '%s<a href="#%d">%s</a>',
                                                $indent,
                                                $item['startLine'],
                                                htmlspecialchars($item['signature'])
                                              ),
            'numMethods'                   => 1,
            'numTestedMethods'             => $numTestedItems,
            'linesExecutedPercent'         => PHP_CodeCoverage_Util::percent(
                                                $item['executedLines'],
                                                $item['executableLines'],
                                                FALSE
                                              ),
            'linesExecutedPercentAsString' => PHP_CodeCoverage_Util::percent(
                                                $item['executedLines'],
                                                $item['executableLines'],
                                                TRUE
                                              ),
            'numExecutedLines'             => $item['executedLines'],
            'numExecutableLines'           => $item['executableLines'],
            'testedMethodsPercent'         => PHP_CodeCoverage_Util::percent(
                                                $numTestedItems,
                                                1,
                                                FALSE
                                              ),
            'testedMethodsPercentAsString' => PHP_CodeCoverage_Util::percent(
                                                $numTestedItems,
                                                1,
                                                TRUE
                                              ),
            'crap'                         => $item['crap']
          )
        );
    }

    /**
     * @param  PHP_CodeCoverage_Report_Node_File $node
     * @return string
     */
    protected function renderSource(PHP_CodeCoverage_Report_Node_File $node)
    {
        if ($this->yui) {
            $yuiTemplate = new Text_Template(
              $this->templatePath . 'yui_item.js'
            );
        }

        $coverageData             = $node->getCoverageData();
        $ignoredLines             = $node->getIgnoredLines();
        $testData                 = $node->getTestData();
        list($codeLines, $fillup) = $this->loadFile($node->getPath());
        $lines                    = '';
        $yuiPanelJS               = '';
        $i                        = 1;

        foreach ($codeLines as $line) {
            $css = '';

            if (!isset($ignoredLines[$i]) && isset($coverageData[$i])) {
                $count    = '';
                $numTests = count($coverageData[$i]);

                if ($coverageData[$i] === NULL) {
                    $color = 'lineDeadCode';
                    $count = '        ';
                }

                else if ($numTests == 0) {
                    $color = 'lineNoCov';
                    $count = sprintf('%8d', 0);
                }

                else {
                    $color = 'lineCov';
                    $count = sprintf('%8d', $numTests);

                    if ($this->yui) {
                        $buffer  = '';
                        $testCSS = '';

                        foreach ($coverageData[$i] as $test) {
                            switch ($testData[$test]) {
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
                              addslashes(htmlspecialchars($test))
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

                        $yuiPanelJS .= $yuiTemplate->render();
                    }
                }

                $css = sprintf(
                  '<span class="%s">       %s : ',

                  $color,
                  $count
                );
            }

            $_fillup = array_shift($fillup);

            if ($_fillup > 0) {
                $line .= str_repeat(' ', $_fillup);
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

        return array($lines, $yuiPanelJS);
    }

    /**
     * @param  string $file
     * @return array
     */
    protected function loadFile($file)
    {
        $buffer = file_get_contents($file);
        $lines  = explode("\n", str_replace("\t", '    ', $buffer));
        $fillup = array();
        $result = array();

        if (count($lines) == 0) {
            return $result;
        }

        $lines       = array_map('rtrim', $lines);
        $linesLength = array_map('strlen', $lines);
        $width       = max($linesLength);

        foreach ($linesLength as $line => $length) {
            $fillup[$line] = $width - $length;
        }

        if (!$this->highlight) {
            unset($lines[count($lines)-1]);
            return array($lines, $fillup);
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
                                case T_AS:
                                case T_BREAK:
                                case T_CASE:
                                case T_CATCH:
                                case T_CLASS:
                                case T_CLONE:
                                case T_CONTINUE:
                                case T_DEFAULT:
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
                                case T_EXIT:
                                case T_EXTENDS:
                                case T_FINAL:
                                case T_FOREACH:
                                case T_FUNCTION:
                                case T_GLOBAL:
                                case T_IF:
                                case T_INCLUDE:
                                case T_INCLUDE_ONCE:
                                case T_INSTANCEOF:
                                case T_ISSET:
                                case T_LOGICAL_AND:
                                case T_LOGICAL_OR:
                                case T_LOGICAL_XOR:
                                case T_NAMESPACE:
                                case T_NEW:
                                case T_PRIVATE:
                                case T_PROTECTED:
                                case T_PUBLIC:
                                case T_REQUIRE:
                                case T_REQUIRE_ONCE:
                                case T_RETURN:
                                case T_STATIC:
                                case T_THROW:
                                case T_TRAIT:
                                case T_TRY:
                                case T_UNSET:
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

        return array($result, $fillup);
    }
}
