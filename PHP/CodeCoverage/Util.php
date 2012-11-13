<?php
/**
 * PHP_CodeCoverage
 *
 * Copyright (c) 2009-2012, Sebastian Bergmann <sb@sebastian-bergmann.de>.
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
 * @copyright  2009-2012 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @link       http://github.com/sebastianbergmann/php-code-coverage
 * @since      File available since Release 1.0.0
 */

/**
 * Utility methods.
 *
 * @category   PHP
 * @package    CodeCoverage
 * @author     Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @copyright  2009-2012 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @link       http://github.com/sebastianbergmann/php-code-coverage
 * @since      Class available since Release 1.0.0
 */
class PHP_CodeCoverage_Util
{
    /**
     * @var array
     */
    protected static $ignoredLines = array();

    /**
     * @var array
     */
    protected static $ids = array();


    /**
     * Returns the lines of a source file that should be ignored.
     *
     * @param  string  $filename
     * @param  boolean $cacheTokens
     * @return array
     * @throws PHP_CodeCoverage_Exception
     */
    public static function getLinesToBeIgnored($filename, $cacheTokens = TRUE)
    {
        if (!is_string($filename)) {
            throw PHP_CodeCoverage_Util_InvalidArgumentHelper::factory(
              1, 'string'
            );
        }

        if (!is_bool($cacheTokens)) {
            throw PHP_CodeCoverage_Util_InvalidArgumentHelper::factory(
              2, 'boolean'
            );
        }

        if (!isset(self::$ignoredLines[$filename])) {
            self::$ignoredLines[$filename] = array();
            $ignore                        = FALSE;
            $stop                          = FALSE;
            $lines                         = file($filename);

            foreach ($lines as $index => $line) {
                if (!trim($line)) {
                    self::$ignoredLines[$filename][$index+1] = TRUE;
                }
            }

            if ($cacheTokens) {
                $tokens = PHP_Token_Stream_CachingFactory::get($filename);
            } else {
                $tokens = new PHP_Token_Stream($filename);
            }

            $classes = array_merge($tokens->getClasses(), $tokens->getTraits());
            $tokens  = $tokens->tokens();

            foreach ($tokens as $token) {
                switch (get_class($token)) {
                    case 'PHP_Token_COMMENT':
                    case 'PHP_Token_DOC_COMMENT': {
                        $count = substr_count($token, "\n");
                        $line  = $token->getLine();

                        for ($i = $line; $i < $line + $count; $i++) {
                            self::$ignoredLines[$filename][$i] = TRUE;
                        }

                        if ($token instanceof PHP_Token_DOC_COMMENT) {
                            // Workaround for the fact the DOC_COMMENT token
                            // does not include the final \n character in its
                            // text.
                            if (substr(trim($lines[$i-1]), -2) == '*/') {
                                self::$ignoredLines[$filename][$i] = TRUE;
                            }

                            break;
                        }

                        $_token = trim($token);

                        if ($_token == '// @codeCoverageIgnore' ||
                            $_token == '//@codeCoverageIgnore') {
                            $ignore = TRUE;
                            $stop   = TRUE;
                        }

                        else if ($_token == '// @codeCoverageIgnoreStart' ||
                                 $_token == '//@codeCoverageIgnoreStart') {
                            $ignore = TRUE;
                        }

                        else if ($_token == '// @codeCoverageIgnoreEnd' ||
                                 $_token == '//@codeCoverageIgnoreEnd') {
                            $stop = TRUE;
                        }
                    }
                    break;

                    case 'PHP_Token_INTERFACE':
                    case 'PHP_Token_TRAIT':
                    case 'PHP_Token_CLASS':
                    case 'PHP_Token_FUNCTION': {
                        $docblock = $token->getDocblock();

                        if (strpos($docblock, '@codeCoverageIgnore')) {
                            $endLine = $token->getEndLine();

                            for ($i = $token->getLine(); $i <= $endLine; $i++) {
                                self::$ignoredLines[$filename][$i] = TRUE;
                            }
                        }

                        else if ($token instanceof PHP_Token_INTERFACE ||
                                 $token instanceof PHP_Token_TRAIT ||
                                 $token instanceof PHP_Token_CLASS) {
                            if (empty($classes[$token->getName()]['methods'])) {
                                for ($i = $token->getLine();
                                     $i <= $token->getEndLine();
                                     $i++) {
                                    self::$ignoredLines[$filename][$i] = TRUE;
                                }
                            } else {
                                $firstMethod = array_shift(
                                  $classes[$token->getName()]['methods']
                                );

                                $lastMethod = array_pop(
                                  $classes[$token->getName()]['methods']
                                );

                                if ($lastMethod === NULL) {
                                    $lastMethod = $firstMethod;
                                }

                                for ($i = $token->getLine();
                                     $i < $firstMethod['startLine'];
                                     $i++) {
                                    self::$ignoredLines[$filename][$i] = TRUE;
                                }

                                for ($i = $token->getEndLine();
                                     $i > $lastMethod['endLine'];
                                     $i--) {
                                    self::$ignoredLines[$filename][$i] = TRUE;
                                }
                            }
                        }
                    }
                    break;

                    case 'PHP_Token_INTERFACE': {
                        $endLine = $token->getEndLine();

                        for ($i = $token->getLine(); $i <= $endLine; $i++) {
                            self::$ignoredLines[$filename][$i] = TRUE;
                        }
                    }
                    break;

                    case 'PHP_Token_NAMESPACE': {
                        self::$ignoredLines[$filename][$token->getEndLine()] = TRUE;
                    } // Intentional fallthrough
                    case 'PHP_Token_OPEN_TAG':
                    case 'PHP_Token_CLOSE_TAG':
                    case 'PHP_Token_USE': {
                        self::$ignoredLines[$filename][$token->getLine()] = TRUE;
                    }
                    break;
                }

                if ($ignore) {
                    self::$ignoredLines[$filename][$token->getLine()] = TRUE;

                    if ($stop) {
                        $ignore = FALSE;
                        $stop   = FALSE;
                    }
                }
            }
        }

        return self::$ignoredLines[$filename];
    }

    /**
     * Checks whether or not it is safe to include file. It ensures that file contains only class/function definitions.
     * It also checks that function/class definitions do not exist prior to including.
     *
     * @param $filename       string      Filename to be checked
     * @param $toplevel_funcs array       List of function calls at top level that are allowed (e.g. array('define'))
     * @param $classes        array       Classes that are present in file with respect of namespaces
     * @param $errmsg         string      Error message will be written to this variable if false is returned
     *
     * @return bool
     */
    public static function canIncludeFile($filename, array $toplevel_funcs, &$classes, &$errmsg)
    {
        if (!is_readable($filename) || !is_file($filename)) return false;
        $source = file_get_contents($filename);
        if ($source === false) return false;
        $tokens = token_get_all($source);
        if ($tokens === false) return false;

        $toplevel_funcs = array_flip($toplevel_funcs);
        $state = "default";
        $depth = 0; // depth of "(" or "{"
        $line = 1;
        $namespace = "\\";
        $classname = "";

        foreach ($tokens as $row) {
            if (is_array($row)) {
                list($token, $text, $line) = $row;
                $text = str_replace("\n", '\\n', $text);
            } else {
                $token = $text = $row;
            }

            if ($token === T_WHITESPACE || $token === T_COMMENT || $token === T_DOC_COMMENT) continue;

//            printf("%-12s ", $state);

            switch ($state) {
                case "funccall":
                    if ($token === "(") $depth++;
                    else if ($token === ")") $depth--;
                    if ($depth == 0) $state = "funccall_end";
                    break;

                case "default":
                    if ($token !== T_OPEN_TAG) {
                        $errmsg = "Have something before <?php tag on line $line";
                        return false;
                    }
                    $state = "root";
                    break;

                case "root":
                    if ($token === T_STRING) { // function call
                        if (!isset($toplevel_funcs[$text])) {
                            $errmsg = "Forbidden top level function call: $text(...) on line $line";
                            return false;
                        }
                        $state = "funccall";
                        $depth = 0;
                    } else if ($token === T_ABSTRACT || $token === T_FINAL) {
                        continue;
                    } else if ($token === T_CLASS) {
                        $state = "classdef";
                        $classname = "";
                    } else if ($token === T_CLOSE_TAG) {
                        $state = "default";
                    } else if ($token === T_NAMESPACE) {
                        $state = "namespace";
                        $namespace = "";
                    } else if ($token === T_USE) {
                        $state = "use";
                    } else if ($token === T_FUNCTION) {
                        $state = "funcdef";
                    } else {
                        $errmsg = "Disallowed top level token '$text' on line $line";
                        return false;
                    }
                    break;

                case "use":
                    if ($row === ";") $state = "root";
                    break;

                case "namespace":
                    if ($token === ";") {
                        $state = "root";
                    } else if ($token === T_NS_SEPARATOR || $token === T_STRING) {
                        $namespace .= $text;
                    } else {
                        $errmsg = "Unexpected token '$row' on line $line (expected ';')";
                        return false;
                    }
                    break;

                case "classdef":
                case "extends":
                case "implements":
                    if ($token === T_EXTENDS || $token === T_IMPLEMENTS || $token === "{") {
                        if (!$classname) {
                            $errmsg = "Empty classname on line $line";
                            return false;
                        }

                        if ($classname[0] != '\\') {
                            $classname = rtrim($namespace, "\\") . "\\" . $classname;
                        }

                        if ($state == "classdef") {
                            if (class_exists($classname)) {
                                $errmsg = "Class '$classname' already exists on line $line";
                                return false;
                            }
                            $classes[] = $classname;
                        }

                        if ($state != "classdef") {
                            $func = ($state == "extends" ? "class_exists" : "interface_exists");
                            if (!$func($classname, true)) {
                                $errmsg = "Class '$classname' does not exist on line $line";
                                return false;
                            }
                        }

                        if ($token === "{") {
                            $state = "class";
                            $depth = 1;
                        } else {
                            $state = ($token == T_EXTENDS ? "extends" : "implements");
                            $classname = "";
                        }
                    } else if ($token === T_STRING || $token == T_NS_SEPARATOR) {
                        $classname .= $text;
                    } else {
                        $errmsg = "Unexpected token '$text' on line $line (expected '{')";
                        return false;
                    }
                    break;

                case "funccall_end":
                    if ($token !== ";") {
                        $errmsg = "Unexpected terminator for function call: '$text' on line $line";
                        return false;
                    }
                    $state = "root";
                    break;

                case "funcdef":
                    if ($row === "{") {
                        $state = "function";
                        $depth = 1;
                    }
                    break;

                case "function":
                    if ($token === "{") $depth++;
                    else if ($token === "}") $depth--;
                    if ($depth == 0) $state = "root";
                    break;

                case "class":
                    if ($token === "{") $depth++;
                    else if ($token === "}") $depth--;
                    if ($depth == 0) $state = "root";
                    break;
            }

//            printf(" => %-15s %-30s %-50s line %d\n", $state, is_int($token) ? token_name($token) : $text, $text, $line);
        }

        return true;
    }

    /**
     * @param  float $a
     * @param  float $b
     * @return float ($a / $b) * 100
     */
    public static function percent($a, $b, $asString = FALSE, $fixedWidth = FALSE)
    {
        if ($asString && $b == 0) {
            return '';
        }

        if ($b > 0) {
            $percent = ($a / $b) * 100;
        } else {
            $percent = 100;
        }

        if ($asString) {
            if ($fixedWidth) {
                return sprintf('%6.2F%%', $percent);
            }

            return sprintf('%01.2F%%', $percent);
        } else {
            return $percent;
        }
    }
}
