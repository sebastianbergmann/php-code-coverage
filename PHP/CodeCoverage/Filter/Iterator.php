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
 * FilterIterator implementation that filters files based on prefix(es) and/or
 * suffix(es). Hidden files and files from hidden directories are also filtered.
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
class PHP_CodeCoverage_Filter_Iterator extends FilterIterator
{
    /**
     * @var array
     */
    protected $suffixes = array();

    /**
     * @var string
     */
    protected $prefixes = array();

    /**
     * @param RecursiveIteratorIterator $iterator
     * @param array|string              $suffixes
     * @param array|string              $prefixes
     */
    public function __construct(RecursiveIteratorIterator $iterator, $suffixes = array(), $prefixes = array())
    {
        if (is_string($suffixes)) {
            if (!empty($suffixes)) {
                $suffixes = array($suffixes);
            } else {
                $suffixes = array();
            }
        }

        if (!is_array($suffixes)) {
            throw new InvalidArgumentException(
              'Argument #2 of PHP_CodeCoverage_Filter_Iterator:__construct()' .
              ' is no array or string.'
            );
        }

        $this->suffixes = $suffixes;

        if (is_string($prefixes)) {
            if (!empty($prefixes)) {
                $prefixes = array($prefixes);
            } else {
                $prefixes = array();
            }
        }

        if (!is_array($prefixes)) {
            throw new InvalidArgumentException(
              'Argument #3 of PHP_CodeCoverage_Filter_Iterator:__construct()' .
              ' is no array or string.'
            );
        }

        $this->prefixes = $prefixes;

        parent::__construct($iterator);
    }

    /**
     * @return boolean
     */
    public function accept()
    {
        $filename = $this->getInnerIterator()->current()->getFilename();

        if (strpos($filename, '.') === 0 ||
            preg_match(
              '=/\.[^/]*/=',
              $this->getInnerIterator()->current()->getPathname())) {
            return FALSE;
        }

        if (!empty($this->prefixes)) {
            $matched = FALSE;

            foreach ($this->prefixes as $prefix) {
                if (strpos($filename, $prefix) === 0) {
                    $matched = TRUE;
                    break;
                }
            }

            if (!$matched) {
                return FALSE;
            }
        }

        if (!empty($this->suffixes)) {
            $matched = FALSE;

            foreach ($this->suffixes as $suffix) {
                if (substr($filename, -1 * strlen($suffix)) == $suffix) {
                    $matched = TRUE;
                    break;
                }
            }

            if (!$matched) {
                return FALSE;
            }
        }

        return TRUE;
    }
}
?>
