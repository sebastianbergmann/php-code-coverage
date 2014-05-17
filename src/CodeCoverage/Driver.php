<?php
/**
 * PHP_CodeCoverage
 *
 * Copyright (c) 2009-2014, Sebastian Bergmann <sebastian@phpunit.de>.
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
 * @copyright  2009-2014 Sebastian Bergmann <sebastian@phpunit.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @link       http://github.com/sebastianbergmann/php-code-coverage
 * @since      File available since Release 2.1.0
 */

/**
 * Base class for code coverage drivers.
 *
 * @category   PHP
 * @package    CodeCoverage
 * @author     Sebastian Bergmann <sebastian@phpunit.de>
 * @copyright  2009-2014 Sebastian Bergmann <sebastian@phpunit.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @link       http://github.com/sebastianbergmann/php-code-coverage
 * @since      Class available since Release 2.1.0
 */
abstract class PHP_CodeCoverage_Driver
{
    /**
     * @var PHP_CodeCoverage_Filter
     */
    private $filter;

    /**
     * @var PHP_CodeCoverage_Parser
     */
    private $parser;

    /**
     * @var array
     */
    private $ignoredLines = array();

    /**
     * @param PHP_CodeCoverage_Filter $filter
     * @param PHP_CodeCoverage_Parser $parser
     */
    public function __construct(PHP_CodeCoverage_Filter $filter, PHP_CodeCoverage_Parser $parser)
    {
        $this->ensureDriverCanWork();

        $this->filter = $filter;
        $this->parser = $parser;
    }

    /**
     * Start collection of code coverage information.
     */
    public function start()
    {
        $this->doStart();
    }

    /**
     * Stop collection of code coverage information.
     *
     * @return array
     */
    public function stop()
    {
        $data = $this->doStop();

        $this->filter($data);
        $this->cleanup($data);

        return $data;
    }

    /**
     * @throws PHP_CodeCoverage_Exception
     */
    abstract protected function ensureDriverCanWork();

    /**
     * Start collection of code coverage information.
     */
    abstract protected function doStart();

    /**
     * Stop collection of code coverage information.
     *
     * @return array
     */
    abstract protected function doStop();

    /**
     * Template method to perform driver-specific data cleanup.
     *
     * @param array $data
     */
    protected function cleanup(array &$data)
    {
    }

    /**
     * Performs blacklist and whitelist as well as @codeCoverageIgnore* filtering.
     *
     * @param array $data
     */
    private function filter(array &$data)
    {
        foreach (array_keys($data) as $filename) {
            if (!file_exists($filename) || $this->filter->isFiltered($filename)) {
                unset($data[$filename]);
                continue;
            }

            foreach ($this->parser->getLinesToBeIgnored($filename) as $line) {
                unset($data[$filename][$line]);
            }

            if (empty($data[$filename])) {
                unset($data[$filename]);
            }
        }
    }
}
