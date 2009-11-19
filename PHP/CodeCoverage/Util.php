<?php
/**
 * PHP_CodeCoverage
 *
 * Copyright (c) 2009, Sebastian Bergmann <sb@sebastian-bergmann.de>.
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
 * @copyright  2009 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link       http://github.com/sebastianbergmann/php-code-coverage
 * @since      File available since Release 1.0.0
 */

/**
 * Utility methods.
 *
 * @category   PHP
 * @package    CodeCoverage
 * @author     Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @copyright  2009 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version    Release: @package_version@
 * @link       http://github.com/sebastianbergmann/php-code-coverage
 * @since      Class available since Release 1.0.0
 */
class PHP_CodeCoverage_Util
{
    /**
     * @var string
     */
    const REGEX = '(@covers\s+(?P<coveredElement>.*?)\s*$)m';

    /**
     * @var array
     */
    protected static $cache = array(
      'classesFunctionsCache' => array(),
      'getLinesToBeIgnored'   => array()
    );

    /**
     * @var array
     */
    protected static $templateMethods = array(
      'setUp', 'assertPreConditions', 'assertPostConditions', 'tearDown'
    );

    /**
     * Builds an array representation of the directory structure.
     *
     * For instance,
     *
     * <code>
     * Array
     * (
     *     [Money.php] => Array
     *         (
     *             ...
     *         )
     *
     *     [MoneyBag.php] => Array
     *         (
     *             ...
     *         )
     * )
     * </code>
     *
     * is transformed into
     *
     * <code>
     * Array
     * (
     *     [.] => Array
     *         (
     *             [Money.php] => Array
     *                 (
     *                     ...
     *                 )
     *
     *             [MoneyBag.php] => Array
     *                 (
     *                     ...
     *                 )
     *         )
     * )
     * </code>
     *
     * @param  array $files
     * @return array
     */
    public static function buildDirectoryStructure($files)
    {
        $result = array();

        foreach ($files as $path => $file) {
            $path    = explode('/', $path);
            $pointer = &$result;
            $max     = count($path);

            for ($i = 0; $i < $max; $i++) {
                if ($i == ($max - 1)) {
                    $type = '/f';
                } else {
                    $type = '';
                }

                $pointer = &$pointer[$path[$i] . $type];
            }

            $pointer = $file;
        }

        return $result;
    }

    /**
     * Counts LOC, CLOC, and NCLOC for a file.
     *
     * @param  string $filename
     * @return array
     */
    public static function countLines($filename)
    {
        $buffer = file_get_contents($filename);
        $loc    = substr_count($buffer, "\n");
        $cloc   = 0;

        foreach (token_get_all($buffer) as $i => $token) {
            if (is_string($token)) {
                continue;
            }

            list ($token, $value) = $token;

            if ($token == T_COMMENT || $token == T_DOC_COMMENT) {
                $cloc += substr_count($value, "\n") + 1;
            }
        }

        return array('loc' => $loc, 'cloc' => $cloc, 'ncloc' => $loc - $cloc);
    }

    /**
     * @param  string $directory
     * @return string
     * @throws RuntimeException
     */
    public static function getDirectory($directory)
    {
        if (substr($directory, -1, 1) != DIRECTORY_SEPARATOR) {
            $directory .= DIRECTORY_SEPARATOR;
        }

        if (is_dir($directory) || mkdir($directory, 0777, TRUE)) {
            return $directory;
        } else {
            throw new RuntimeException(
              sprintf(
                'Directory "%s" does not exist.',
                $directory
              )
            );
        }
    }

    /**
     * Returns information on the classes declared in a sourcefile.
     *
     * @param  string $filename
     * @return array
     */
    public static function getClassesInFile($filename)
    {
        if (!isset(self::$cache['classesFunctionsCache'][$filename])) {
            self::parseFile($filename);
        }

        return self::$cache['classesFunctionsCache'][$filename]['classes'];
    }

    /**
     * Returns information on the functions declared in a sourcefile.
     *
     * @param  string $filename
     * @return array
     */
    public static function getFunctionsInFile($filename)
    {
        if (!isset(self::$cache['classesFunctionsCache'][$filename])) {
            self::parseFile($filename);
        }

        return self::$cache['classesFunctionsCache'][$filename]['functions'];
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

        if (($pos = strpos($methodName, ' ')) !== FALSE) {
            $methodName = substr($methodName, 0, $pos);
        }

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

        if (preg_match_all(self::REGEX, $docComment, $matches)) {
            foreach ($matches['coveredElement'] as $coveredElement) {
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
     * @param  string $filename
     * @return array
     */
    public static function getLinesToBeIgnored($filename)
    {
        if (!isset(self::$cache['getLinesToBeIgnored'][$filename])) {
            self::$cache['getLinesToBeIgnored'][$filename] = array();

            $ignore  = FALSE;
            $lineNum = 1;

            foreach (file($filename) as $line) {
                $trimmedLine = trim($line);

                if ($trimmedLine == '// @codeCoverageIgnoreStart') {
                    $ignore = TRUE;
                }

                if ($ignore) {
                    self::$cache['getLinesToBeIgnored'][$filename][] = $lineNum;

                    if ($trimmedLine == '// @codeCoverageIgnoreEnd') {
                        $ignore = FALSE;
                    }
                }

                $lineNum++;
            }
        }

        return self::$cache['getLinesToBeIgnored'][$filename];
    }

    /**
     * Returns the package information of a user-defined class.
     *
     * @param  string $className
     * @param  string $docComment
     * @return array
     */
    public static function getPackageInformation($className, $docComment)
    {
        $result = array(
          'namespace'   => '',
          'fullPackage' => '',
          'category'    => '',
          'package'     => '',
          'subpackage'  => ''
        );

        if (strpos($className, '\\') !== FALSE) {
            $result['namespace'] = self::arrayToName(
              explode('\\', $className)
            );
        }

        if (preg_match('/@category[\s]+([\.\w]+)/', $docComment, $matches)) {
            $result['category'] = $matches[1];
        }

        if (preg_match('/@package[\s]+([\.\w]+)/', $docComment, $matches)) {
            $result['package']     = $matches[1];
            $result['fullPackage'] = $matches[1];
        }

        if (preg_match('/@subpackage[\s]+([\.\w]+)/', $docComment, $matches)) {
            $result['subpackage']   = $matches[1];
            $result['fullPackage'] .= '.' . $matches[1];
        }

        if (empty($result['fullPackage'])) {
            $result['fullPackage'] = self::arrayToName(
              explode('_', str_replace('\\', '_', $className)), '.'
            );
        }

        return $result;
    }

    /**
     * Returns a filesystem safe version of the passed filename.
     * This function does not operate on full paths, just filenames.
     *
     * @param  string $filename
     * @return string
     * @author Michael Lively Jr. <m@digitalsandwich.com>
     */
    public static function getSafeFilename($filename)
    {
        /* characters allowed: A-Z, a-z, 0-9, _ and . */
        return preg_replace('#[^\w.]#', '_', $filename);
    }

    /**
     * Reduces the paths by cutting the longest common start path.
     *
     * For instance,
     *
     * <code>
     * Array
     * (
     *     [/home/sb/Money/Money.php] => Array
     *         (
     *             ...
     *         )
     *
     *     [/home/sb/Money/MoneyBag.php] => Array
     *         (
     *             ...
     *         )
     * )
     * </code>
     *
     * is reduced to
     *
     * <code>
     * Array
     * (
     *     [Money.php] => Array
     *         (
     *             ...
     *         )
     *
     *     [MoneyBag.php] => Array
     *         (
     *             ...
     *         )
     * )
     * </code>
     *
     * @param  array $files
     * @return string
     */
    public static function reducePaths(&$files)
    {
        if (empty($files)) {
            return '.';
        }

        $commonPath = '';
        $paths      = array_keys($files);

        if (count($files) == 1) {
            $commonPath                 = dirname($paths[0]);
            $files[basename($paths[0])] = $files[$paths[0]];

            unset($files[$paths[0]]);

            return $commonPath;
        }

        $max = count($paths);

        for ($i = 0; $i < $max; $i++) {
            $paths[$i] = explode(DIRECTORY_SEPARATOR, $paths[$i]);

            if (empty($paths[$i][0])) {
                $paths[$i][0] = DIRECTORY_SEPARATOR;
            }
        }

        $done = FALSE;
        $max  = count($paths);

        while (!$done) {
            for ($i = 0; $i < $max - 1; $i++) {
                if (!isset($paths[$i][0]) ||
                    !isset($paths[$i+1][0]) ||
                    $paths[$i][0] != $paths[$i+1][0]) {
                    $done = TRUE;
                    break;
                }
            }

            if (!$done) {
                $commonPath .= $paths[0][0] . (($paths[0][0] != DIRECTORY_SEPARATOR) ? DIRECTORY_SEPARATOR : '');

                for ($i = 0; $i < $max; $i++) {
                    array_shift($paths[$i]);
                }
            }
        }

        $original = array_keys($files);
        $max      = count($original);

        for ($i = 0; $i < $max; $i++) {
            $files[join('/', $paths[$i])] = $files[$original[$i]];
            unset($files[$original[$i]]);
        }

        ksort($files);

        return $commonPath;
    }

    /**
     * Returns the package information of a user-defined class.
     *
     * @param  array  $parts
     * @param  string $join
     * @return string
     */
    protected static function arrayToName(array $parts, $join = '\\')
    {
        $result = '';

        if (count($parts) > 1) {
            array_pop($parts);

            $result = join($join, $parts);
        }

        return $result;
    }

    /**
     * Parses a file for class, method, and function information.
     *
     * @param string $filename
     */
    protected static function parseFile($filename)
    {
        self::$cache['classesFunctionsCache'][$filename] = array(
          'classes' => array(), 'functions' => array()
        );

        $tokens                     = token_get_all(
                                        file_get_contents($filename)
                                      );
        $numTokens                  = count($tokens);
        $blocks                     = array();
        $line                       = 1;
        $currentBlock               = FALSE;
        $currentNamespace           = FALSE;
        $currentClass               = FALSE;
        $currentFunction            = FALSE;
        $currentFunctionStartLine   = FALSE;
        $currentFunctionTokens      = array();
        $currentDocComment          = FALSE;
        $currentSignature           = FALSE;
        $currentSignatureStartToken = FALSE;

        for ($i = 0; $i < $numTokens; $i++) {
            if ($currentFunction !== FALSE) {
                $currentFunctionTokens[] = $tokens[$i];
            }

            if (is_string($tokens[$i])) {
                if ($tokens[$i] == '{') {
                    if ($currentBlock == T_CLASS) {
                        $block = $currentClass;
                    }

                    else if ($currentBlock == T_FUNCTION) {
                        $currentSignature = '';

                        for ($j = $currentSignatureStartToken; $j < $i; $j++) {
                            if (is_string($tokens[$j])) {
                                $currentSignature .= $tokens[$j];
                            } else {
                                $currentSignature .= $tokens[$j][1];
                            }
                        }

                        $currentSignature = trim($currentSignature);

                        $block                      = $currentFunction;
                        $currentSignatureStartToken = FALSE;
                    }

                    else {
                        $block = FALSE;
                    }

                    array_push($blocks, $block);

                    $currentBlock = FALSE;
                }

                else if ($tokens[$i] == '}') {
                    $block = array_pop($blocks);

                    if ($block !== FALSE && $block !== NULL) {
                        if ($block == $currentFunction) {
                            if ($currentDocComment !== FALSE) {
                                $docComment        = $currentDocComment;
                                $currentDocComment = FALSE;
                            } else {
                                $docComment = '';
                            }

                            $tmp = array(
                              'docComment' => $docComment,
                              'signature'  => $currentSignature,
                              'startLine'  => $currentFunctionStartLine,
                              'endLine'    => $line,
                              'tokens'     => $currentFunctionTokens
                            );

                            if ($currentClass === FALSE) {
                                self::$cache['classesFunctionsCache'][$filename]['functions'][$currentFunction] = $tmp;
                            } else {
                                self::$cache['classesFunctionsCache'][$filename]['classes'][$currentClass]['methods'][$currentFunction] = $tmp;
                            }

                            $currentFunction          = FALSE;
                            $currentFunctionStartLine = FALSE;
                            $currentFunctionTokens    = array();
                            $currentSignature         = FALSE;
                        }

                        else if ($block == $currentClass) {
                            self::$cache['classesFunctionsCache'][$filename]['classes'][$currentClass]['endLine'] = $line;

                            $currentClass          = FALSE;
                            $currentClassStartLine = FALSE;
                        }
                    }
                }

                continue;
            }

            switch ($tokens[$i][0]) {
                case T_NAMESPACE: {
                    $currentNamespace = $tokens[$i+2][1];

                    for ($j = $i+3; $j < $numTokens; $j += 2) {
                        if ($tokens[$j][0] == T_NS_SEPARATOR) {
                            $currentNamespace .= '\\' . $tokens[$j+1][1];
                        } else {
                            break;
                        }
                    }
                }
                break;

                case T_CURLY_OPEN: {
                    $currentBlock = T_CURLY_OPEN;
                    array_push($blocks, $currentBlock);
                }
                break;

                case T_DOLLAR_OPEN_CURLY_BRACES: {
                    $currentBlock = T_DOLLAR_OPEN_CURLY_BRACES;
                    array_push($blocks, $currentBlock);
                }
                break;

                case T_CLASS: {
                    $currentBlock = T_CLASS;

                    if ($currentNamespace === FALSE) {
                        $currentClass = $tokens[$i+2][1];
                    } else {
                        $currentClass = $currentNamespace . '\\' .
                                        $tokens[$i+2][1];
                    }

                    if ($currentDocComment !== FALSE) {
                        $docComment        = $currentDocComment;
                        $currentDocComment = FALSE;
                    } else {
                        $docComment = '';
                    }

                    self::$cache['classesFunctionsCache'][$filename]['classes'][$currentClass] = array(
                      'methods'    => array(),
                      'docComment' => $docComment,
                      'startLine'  => $line
                    );
                }
                break;

                case T_FUNCTION: {
                    $currentBlock             = T_FUNCTION;
                    $currentFunctionStartLine = $line;

                    $done                       = FALSE;
                    $currentSignatureStartToken = $i - 1;

                    do {
                        switch ($tokens[$currentSignatureStartToken][0]) {
                            case T_ABSTRACT:
                            case T_FINAL:
                            case T_PRIVATE:
                            case T_PUBLIC:
                            case T_PROTECTED:
                            case T_STATIC:
                            case T_WHITESPACE: {
                                $currentSignatureStartToken--;
                            }
                            break;

                            default: {
                                $currentSignatureStartToken++;
                                $done = TRUE;
                            }
                        }
                    }
                    while (!$done);

                    if (isset($tokens[$i+2][1])) {
                        $functionName = $tokens[$i+2][1];
                    }

                    else if (isset($tokens[$i+3][1])) {
                        $functionName = $tokens[$i+3][1];
                    }

                    if ($currentNamespace === FALSE) {
                        $currentFunction = $functionName;
                    } else {
                        $currentFunction = $currentNamespace . '\\' .
                                           $functionName;
                    }
                }
                break;

                case T_DOC_COMMENT: {
                    $currentDocComment = $tokens[$i][1];
                }
                break;
            }

            $line += substr_count($tokens[$i][1], "\n");
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

            if ($methodName{0} == '<') {
                $classes = array($className);

                foreach ($classes as $className) {
                    if (!class_exists($className) &&
                        !interface_exists($className)) {
                        throw new RuntimeException(
                          sprintf(
                            'Trying to @cover not existing class or ' .
                            'interface "%s".',
                            $className
                          )
                        );
                    }

                    $class   = new ReflectionClass($className);
                    $methods = $class->getMethods();
                    $inverse = isset($methodName{1}) && $methodName{1} == '!';

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
                    if (!((class_exists($className) ||
                           interface_exists($className)) &&
                          method_exists($className, $methodName))) {
                        throw new RuntimeException(
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
                    !interface_exists($className)) {
                    throw new RuntimeException(
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
?>
