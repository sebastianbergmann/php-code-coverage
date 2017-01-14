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
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link       http://github.com/sebastianbergmann/php-code-coverage
 * @since      File available since Release 1.0.0
 */

/**
 * Driver for Xdebug's code coverage functionality.
 *
 * @category   PHP
 * @package    CodeCoverage
 * @author     Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @copyright  2009-2012 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version    Release: @package_version@
 * @link       http://github.com/sebastianbergmann/php-code-coverage
 * @since      Class available since Release 1.0.0
 * @codeCoverageIgnore
 */
class PHP_CodeCoverage_Driver_Xdebug implements PHP_CodeCoverage_Driver
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        if (!extension_loaded('xdebug')) {
            throw new PHP_CodeCoverage_Exception('Xdebug is not loaded.');
        }

        if (version_compare(phpversion('xdebug'), '2.2.0-dev', '>=') &&
            !ini_get('xdebug.coverage_enable')) {
            throw new PHP_CodeCoverage_Exception(
              'You need to set xdebug.coverage_enable=On in your php.ini.'
            );
        }
    }

    /**
     * Start collection of code coverage information.
     */
    public function start()
    {
        xdebug_start_code_coverage(XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE);
    }

    /**
     * Stop collection of code coverage information.
     *
     * @return array
     */
    public function stop()
    {
        $codeCoverage = xdebug_get_code_coverage();
        xdebug_stop_code_coverage();
        $codeCoverage = self::cleanFilenames($codeCoverage);

        return $codeCoverage;
    }

    /**
     * By-pass http://bugs.xdebug.org/bug_view_page.php?bug_id=0000331
     *
     * This Xdebug bug causes some filenames to be corrupted in the form
     * "[..]/wrongreturn.php(19) : assert code"
     * instead of
     * "[..]/wrongreturn.php"
     * The goal of this function is to by-pass the bug until it is fixed in Xdebug
     * by cleaning corrupted filenames
     *
     * @return array
     */
    protected static function cleanFilenames($data)
    {
        foreach ($data as $file => $lines) {
            // check the existence of the wrong pattern in filename
            $correct_file = preg_replace('/\(\d+\) :.+/', '', $file);
            if ($file != $correct_file) {
                // if wrong filename found, we merge code coverage data
                // with correct filename
                if (!array_key_exists($correct_file, $data)) {
                    $data[$correct_file] = array();
                }
                $data[$correct_file] += $lines;
                ksort($data[$correct_file]);
                // and unset wrong filename data
                unset($data[$file]);
            }
        }

        return $data;
    }
}
