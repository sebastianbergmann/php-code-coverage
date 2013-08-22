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
 * Factory for PHP_CodeCoverage_Report_Node_* object graphs.
 *
 * @category   PHP
 * @package    CodeCoverage
 * @author     Sebastian Bergmann <sebastian@phpunit.de>
 * @copyright  2009-2013 Sebastian Bergmann <sebastian@phpunit.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @link       http://github.com/sebastianbergmann/php-code-coverage
 * @since      Class available since Release 1.1.0
 */
class PHP_CodeCoverage_Report_Factory
{
    /**
     * @param PHP_CodeCoverage $coverage
     */
    public function create(PHP_CodeCoverage $coverage)
    {
        $files      = $coverage->getData();
        $commonPath = PHP_CodeCoverage_Util::reducePaths($files);
        $root       = new PHP_CodeCoverage_Report_Node_Directory(
                        $commonPath, NULL
                      );

        $this->addItems(
          $root,
          $this->buildDirectoryStructure($files),
          $coverage->getTests(),
          $coverage->getCacheTokens()
        );

        return $root;
    }

    /**
     * @param PHP_CodeCoverage_Report_Node_Directory $root
     * @param array                                  $items
     * @param array                                  $tests
     * @param boolean                                $cacheTokens
     */
    protected function addItems(PHP_CodeCoverage_Report_Node_Directory $root, array $items, array $tests, $cacheTokens)
    {
        foreach ($items as $key => $value) {
            if (substr($key, -2) == '/f') {
                $key = substr($key, 0, -2);

                if (file_exists($root->getPath() . DIRECTORY_SEPARATOR . $key)) {
                    $root->addFile($key, $value, $tests, $cacheTokens);
                }
            } else {
                $child = $root->addDirectory($key);
                $this->addItems($child, $value, $tests, $cacheTokens);
            }
        }
    }

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
    protected function buildDirectoryStructure($files)
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
}
