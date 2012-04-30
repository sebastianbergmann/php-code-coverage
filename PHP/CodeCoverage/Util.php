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

// @codeCoverageIgnoreStart
// @codingStandardsIgnoreStart
/**
 * @SuppressWarnings(PHPMD)
 */
if (!function_exists('trait_exists')) {
    function trait_exists($name)
    {
        return FALSE;
    }
}
// @codingStandardsIgnoreEnd
// @codeCoverageIgnoreEnd

/**
 * Utility methods.
 *
 * @category   PHP
 * @package    CodeCoverage
 * @author     Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @copyright  2009-2012 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @version    Release: @package_version@
 * @link       http://github.com/sebastianbergmann/php-code-coverage
 * @since      Class available since Release 1.0.0
 */
class PHP_CodeCoverage_Util
{
    /**
     * @var string
     */
    const COVERS_REGEX = '(@covers\s+(?P<coveredElement>.*?)\s*$)m';

    /**
     * @var string
     */
    const COVERS_DEFAULT_CLASS_REGEX = '(@coversDefaultClass\s+(?P<coveredClass>.*?)\s*$)m';

    /**
     * @var array
     */
    protected static $ignoredLines = array();

    /**
     * @var array
     */
    protected static $templateMethods = array(
      'setUp', 'assertPreConditions', 'assertPostConditions', 'tearDown'
    );

    /**
     * @var array
     */
    protected static $ids = array();

    /**
     * Calculates the Change Risk Anti-Patterns (CRAP) index for a unit of code
     * based on its cyclomatic complexity and percentage of code coverage.
     *
     * @param  integer $ccn
     * @param  float   $coverage
     * @return string
     */
    public static function crap($ccn, $coverage)
    {
        if ($coverage == 0) {
            return (string)pow($ccn, 2) + $ccn;
        }

        if ($coverage >= 95) {
            return (string)$ccn;
        }

        return sprintf(
          '%01.2F', pow($ccn, 2) * pow(1 - $coverage/100, 3) + $ccn
        );
    }

    /**
     * @param  string $directory
     * @return string
     * @throws PHP_CodeCoverage_Exception
     */
    public static function getDirectory($directory)
    {
        if (substr($directory, -1, 1) != DIRECTORY_SEPARATOR) {
            $directory .= DIRECTORY_SEPARATOR;
        }

        if (is_dir($directory)) {
            return $directory;
        }

        if (mkdir($directory, 0777, TRUE)) {
            return $directory;
        }

        throw new PHP_CodeCoverage_Exception(
          sprintf(
            'Directory "%s" does not exist.',
            $directory
          )
        );
    }

    /**
     * Returns the files and lines a test method wants to cover.
     *
     * @param  string $className
     * @param  string $methodName
     * @return array
     */
    public static function getLinesToBeCovered($className, $methodName)
    {
        $codeToCoverList = array();
        $result          = array();
        // @codeCoverageIgnoreStart
        if (($pos = strpos($methodName, ' ')) !== FALSE) {
            $methodName = substr($methodName, 0, $pos);
        }
        // @codeCoverageIgnoreEnd
        $class      = new ReflectionClass($className);
        $method     = new ReflectionMethod($className, $methodName);
        $docComment = $class->getDocComment() . $method->getDocComment();

        foreach (self::$templateMethods as $templateMethod) {
            if ($class->hasMethod($templateMethod)) {
                $reflector   = $class->getMethod($templateMethod);
                $docComment .= $reflector->getDocComment();
                unset($reflector);
            }
        }

        if (strpos($docComment, '@coversNothing') !== FALSE) {
            return $result;
        }

        $classShortcut = preg_match_all(
          self::COVERS_DEFAULT_CLASS_REGEX, $class->getDocComment(), $matches
        );

        if ($classShortcut) {
            if ($classShortcut > 1) {
                throw new PHP_CodeCoverage_Exception(
                  sprintf(
                    'More than one @coversClass annotation in class or interface "%s".',
                    $className
                  )
                );
            }

            $classShortcut = $matches['coveredClass'][0];
        }

        if (preg_match_all(self::COVERS_REGEX, $docComment, $matches)) {
            foreach ($matches['coveredElement'] as $coveredElement) {
                if ($classShortcut && strncmp($coveredElement, '::', 2) === 0) {
                    $coveredElement = $classShortcut . $coveredElement;
                }

                $codeToCoverList = array_merge(
                  $codeToCoverList,
                  self::resolveCoversToReflectionObjects($coveredElement)
                );
            }

            foreach ($codeToCoverList as $codeToCover) {
                $fileName = $codeToCover->getFileName();

                if (!isset($result[$fileName])) {
                    $result[$fileName] = array();
                }

                $result[$fileName] = array_unique(
                  array_merge(
                    $result[$fileName],
                    range(
                      $codeToCover->getStartLine(), $codeToCover->getEndLine()
                    )
                  )
                );
            }
        }

        return $result;
    }

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

            foreach (file($filename) as $index => $line) {
                if (!trim($line)) {
                    self::$ignoredLines[$filename][$index+1] = TRUE;
                }
            }

            if ($cacheTokens) {
                $tokens = PHP_Token_Stream_CachingFactory::get($filename);
            } else {
                $tokens = new PHP_Token_Stream($filename);
            }

            $classes = $tokens->getClasses();
            $tokens  = $tokens->tokens();

            foreach ($tokens as $token) {
                switch (get_class($token)) {
                    case 'PHP_Token_CLASS':
                    case 'PHP_Token_FUNCTION': {
                        $docblock = $token->getDocblock();

                        if (strpos($docblock, '@codeCoverageIgnore')) {
                            $endLine = $token->getEndLine();

                            for ($i = $token->getLine(); $i <= $endLine; $i++) {
                                self::$ignoredLines[$filename][$i] = TRUE;
                            }
                        }

                        else if ($token instanceof PHP_Token_CLASS &&
                                 !empty($classes[$token->getName()]['methods'])) {
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

                    case 'PHP_Token_COMMENT': {
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
     * @param  float $a
     * @param  float $b
     * @return float ($a / $b) * 100
     */
    public static function percent($a, $b, $asString = FALSE, $fixedWidth = FALSE)
    {
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

    /**
     * @param  string $coveredElement
     * @return array
     */
    protected static function resolveCoversToReflectionObjects($coveredElement)
    {
        $codeToCoverList = array();

        if (strpos($coveredElement, '::') !== FALSE) {
            list($className, $methodName) = explode('::', $coveredElement);

            if (isset($methodName[0]) && $methodName[0] == '<') {
                $classes = array($className);

                foreach ($classes as $className) {
                    if (!class_exists($className) &&
                        !interface_exists($className)) {
                        throw new PHP_CodeCoverage_Exception(
                          sprintf(
                            'Trying to @cover not existing class or ' .
                            'interface "%s".',
                            $className
                          )
                        );
                    }

                    $class   = new ReflectionClass($className);
                    $methods = $class->getMethods();
                    $inverse = isset($methodName[1]) && $methodName[1] == '!';

                    if (strpos($methodName, 'protected')) {
                        $visibility = 'isProtected';
                    }

                    else if (strpos($methodName, 'private')) {
                        $visibility = 'isPrivate';
                    }

                    else if (strpos($methodName, 'public')) {
                        $visibility = 'isPublic';
                    }

                    foreach ($methods as $method) {
                        if ($inverse && !$method->$visibility()) {
                            $codeToCoverList[] = $method;
                        }

                        else if (!$inverse && $method->$visibility()) {
                            $codeToCoverList[] = $method;
                        }
                    }
                }
            } else {
                $classes = array($className);

                foreach ($classes as $className) {
                    if ($className == '' && function_exists($methodName)) {
                        $codeToCoverList[] = new ReflectionFunction(
                          $methodName
                        );
                    } else {
                        if (!((class_exists($className) ||
                               interface_exists($className) ||
                               trait_exists($className)) &&
                              method_exists($className, $methodName))) {
                            throw new PHP_CodeCoverage_Exception(
                              sprintf(
                                'Trying to @cover not existing method "%s::%s".',
                                $className,
                                $methodName
                              )
                            );
                        }

                        $codeToCoverList[] = new ReflectionMethod(
                          $className, $methodName
                        );
                    }
                }
            }
        } elseif (strpos($coveredElement, '.') !== FALSE) {
            $file = stream_resolve_include_path($coveredElement);
            if (!$file) {
                throw new PHP_CodeCoverage_Exception(
                  sprintf(
                    'Trying to @cover not existing file "%s".',
                    $coveredElement
                  )
                );
            }

            $codeToCoverList[] = new PHP_CodeCoverage_ReflectionFile($file);
        } else {
            $extended = FALSE;

            if (strpos($coveredElement, '<extended>') !== FALSE) {
                $coveredElement = str_replace(
                  '<extended>', '', $coveredElement
                );

                $extended = TRUE;
            }

            $classes = array($coveredElement);

            if ($extended) {
                $classes = array_merge(
                  $classes,
                  class_implements($coveredElement),
                  class_parents($coveredElement)
                );
            }

            foreach ($classes as $className) {
                if (!class_exists($className) &&
                    !interface_exists($className) &&
                    !trait_exists($className)) {
                    throw new PHP_CodeCoverage_Exception(
                      sprintf(
                        'Trying to @cover not existing class or ' .
                        'interface "%s".',
                        $className
                      )
                    );
                }

                $codeToCoverList[] = new ReflectionClass($className);
            }
        }

        return $codeToCoverList;
    }
}
