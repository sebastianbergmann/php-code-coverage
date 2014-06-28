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
 * @since      File available since Release 1.0.0
 */

/**
 * Filter for blacklisting and whitelisting of code coverage information.
 *
 * @category   PHP
 * @package    CodeCoverage
 * @author     Sebastian Bergmann <sebastian@phpunit.de>
 * @copyright  2009-2014 Sebastian Bergmann <sebastian@phpunit.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @link       http://github.com/sebastianbergmann/php-code-coverage
 * @since      Class available since Release 1.0.0
 */
class PHP_CodeCoverage_Filter
{
    /**
     * Source files that are blacklisted.
     *
     * @var array
     */
    private $blacklistedFiles = array();

    /**
     * Source files that are whitelisted.
     *
     * @var array
     */
    private $whitelistedFiles = array();

    /**
     * @var boolean
     */
    private $blacklistPrefilled = false;

    /**
     * A list of classes which are always blacklisted
     *
     * @var array
     */
    public static $blacklistClassNames = array(
        'File_Iterator' => 1,
        'PHP_CodeCoverage' => 1,
        'PHP_Invoker' => 1,
        'PHP_Timer' => 1,
        'PHP_Token' => 1,
        'PHPUnit_Framework_TestCase' => 2,
        'PHPUnit_Extensions_Database_TestCase' => 2,
        'PHPUnit_Framework_MockObject_Generator' => 2,
        'PHPUnit_Extensions_SeleniumTestCase' => 2,
        'PHPUnit_Extensions_Story_TestCase' => 2,
        'Text_Template' => 1,
        'Symfony\Component\Yaml\Yaml' => 1,
        'SebastianBergmann\Diff\Diff' => 1,
        'SebastianBergmann\Comparator\Comparator' => 1,
        'SebastianBergmann\Environment\Runtime' => 1,
        'SebastianBergmann\Exporter\Exporter' => 1,
        'SebastianBergmann\Version' => 1,
        'Composer\Autoload\ClassLoader' => 1,
        'Instantiator\Instantiator' => 1,
        'LazyMap\AbstractLazyMap' => 1
    );

    /**
     * Adds a directory to the blacklist (recursively).
     *
     * @param string $directory
     * @param string $suffix
     * @param string $prefix
     */
    public function addDirectoryToBlacklist($directory, $suffix = '.php', $prefix = '')
    {
        $facade = new File_Iterator_Facade;
        $files  = $facade->getFilesAsArray($directory, $suffix, $prefix);

        foreach ($files as $file) {
            $this->addFileToBlacklist($file);
        }
    }

    /**
     * Adds a file to the blacklist.
     *
     * @param string $filename
     */
    public function addFileToBlacklist($filename)
    {
        $this->blacklistedFiles[realpath($filename)] = true;
    }

    /**
     * Adds files to the blacklist.
     *
     * @param array $files
     */
    public function addFilesToBlacklist(array $files)
    {
        foreach ($files as $file) {
            $this->addFileToBlacklist($file);
        }
    }

    /**
     * Removes a directory from the blacklist (recursively).
     *
     * @param string $directory
     * @param string $suffix
     * @param string $prefix
     */
    public function removeDirectoryFromBlacklist($directory, $suffix = '.php', $prefix = '')
    {
        $facade = new File_Iterator_Facade;
        $files  = $facade->getFilesAsArray($directory, $suffix, $prefix);

        foreach ($files as $file) {
            $this->removeFileFromBlacklist($file);
        }
    }

    /**
     * Removes a file from the blacklist.
     *
     * @param string $filename
     */
    public function removeFileFromBlacklist($filename)
    {
        $filename = realpath($filename);

        if (isset($this->blacklistedFiles[$filename])) {
            unset($this->blacklistedFiles[$filename]);
        }
    }

    /**
     * Adds a directory to the whitelist (recursively).
     *
     * @param string $directory
     * @param string $suffix
     * @param string $prefix
     */
    public function addDirectoryToWhitelist($directory, $suffix = '.php', $prefix = '')
    {
        $facade = new File_Iterator_Facade;
        $files  = $facade->getFilesAsArray($directory, $suffix, $prefix);

        foreach ($files as $file) {
            $this->addFileToWhitelist($file);
        }
    }

    /**
     * Adds a file to the whitelist.
     *
     * @param string $filename
     */
    public function addFileToWhitelist($filename)
    {
        $this->whitelistedFiles[realpath($filename)] = true;
    }

    /**
     * Adds files to the whitelist.
     *
     * @param array $files
     */
    public function addFilesToWhitelist(array $files)
    {
        foreach ($files as $file) {
            $this->addFileToWhitelist($file);
        }
    }

    /**
     * Removes a directory from the whitelist (recursively).
     *
     * @param string $directory
     * @param string $suffix
     * @param string $prefix
     */
    public function removeDirectoryFromWhitelist($directory, $suffix = '.php', $prefix = '')
    {
        $facade = new File_Iterator_Facade;
        $files  = $facade->getFilesAsArray($directory, $suffix, $prefix);

        foreach ($files as $file) {
            $this->removeFileFromWhitelist($file);
        }
    }

    /**
     * Removes a file from the whitelist.
     *
     * @param string $filename
     */
    public function removeFileFromWhitelist($filename)
    {
        $filename = realpath($filename);

        if (isset($this->whitelistedFiles[$filename])) {
            unset($this->whitelistedFiles[$filename]);
        }
    }

    /**
     * Checks whether a filename is a real filename.
     *
     * @param string $filename
     */
    public function isFile($filename)
    {
        if ($filename == '-' ||
            strpos($filename, 'vfs://') === 0 ||
            strpos($filename, 'xdebug://debug-eval') !== false ||
            strpos($filename, 'eval()\'d code') !== false ||
            strpos($filename, 'runtime-created function') !== false ||
            strpos($filename, 'runkit created function') !== false ||
            strpos($filename, 'assert code') !== false ||
            strpos($filename, 'regexp code') !== false) {
            return false;
        }

        return file_exists($filename);
    }

    /**
     * Checks whether or not a file is filtered.
     *
     * When the whitelist is empty (default), blacklisting is used.
     * When the whitelist is not empty, whitelisting is used.
     *
     * @param  string                     $filename
     * @param  boolean                    $ignoreWhitelist
     * @return boolean
     * @throws PHP_CodeCoverage_Exception
     */
    public function isFiltered($filename)
    {
        if (!$this->isFile($filename)) {
            return true;
        }

        $filename = realpath($filename);

        if (!empty($this->whitelistedFiles)) {
            return !isset($this->whitelistedFiles[$filename]);
        }

        if (!$this->blacklistPrefilled) {
            $this->prefillBlacklist();
        }

        return isset($this->blacklistedFiles[$filename]);
    }

    /**
     * Returns the list of blacklisted files.
     *
     * @return array
     */
    public function getBlacklist()
    {
        return array_keys($this->blacklistedFiles);
    }

    /**
     * Returns the list of whitelisted files.
     *
     * @return array
     */
    public function getWhitelist()
    {
        return array_keys($this->whitelistedFiles);
    }

    /**
     * Returns whether this filter has a whitelist.
     *
     * @return boolean
     * @since  Method available since Release 1.1.0
     */
    public function hasWhitelist()
    {
        return !empty($this->whitelistedFiles);
    }

    /**
     * @since Method available since Release 1.2.3
     */
    private function prefillBlacklist()
    {
        if (defined('__PHPUNIT_PHAR__')) {
            $this->addFileToBlacklist(__PHPUNIT_PHAR__);
        }

        foreach (self::$blacklistClassNames as $className => $parent) {
            $this->addDirectoryContainingClassToBlacklist($className, $parent);
        }

        $this->blacklistPrefilled = true;
    }

    /**
     * @param string  $className
     * @param integer $parent
     * @since Method available since Release 1.2.3
     */
    private function addDirectoryContainingClassToBlacklist($className, $parent = 1)
    {
        if (!class_exists($className)) {
            return;
        }

        $reflector = new ReflectionClass($className);
        $directory = $reflector->getFileName();

        for ($i = 0; $i < $parent; $i++) {
            $directory = dirname($directory);
        }

        $this->addDirectoryToBlacklist($directory);
    }

    /**
     * Returns the blacklisted files.
     *
     * @return array
     * @since Method available since Release 2.0.0
     */
    public function getBlacklistedFiles()
    {
        return $this->blacklistedFiles;
    }

    /**
     * Sets the blacklisted files.
     *
     * @param array $blacklistedFiles
     * @since Method available since Release 2.0.0
     */
    public function setBlacklistedFiles($blacklistedFiles)
    {
        $this->blacklistedFiles = $blacklistedFiles;
    }

    /**
     * Returns the whitelisted files.
     *
     * @return array
     * @since Method available since Release 2.0.0
     */
    public function getWhitelistedFiles()
    {
        return $this->whitelistedFiles;
    }

    /**
     * Sets the whitelisted files.
     *
     * @param array $whitelistedFiles
     * @since Method available since Release 2.0.0
     */
    public function setWhitelistedFiles($whitelistedFiles)
    {
        $this->whitelistedFiles = $whitelistedFiles;
    }
}
