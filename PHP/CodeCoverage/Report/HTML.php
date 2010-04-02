<?php
/**
 * PHP_CodeCoverage
 *
 * Copyright (c) 2009-2010, Sebastian Bergmann <sb@sebastian-bergmann.de>.
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
 * @copyright  2009-2010 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link       http://github.com/sebastianbergmann/php-code-coverage
 * @since      File available since Release 1.0.0
 */

require_once 'PHP/CodeCoverage.php';
require_once 'PHP/CodeCoverage/Report/HTML/Node.php';
require_once 'Text/Template.php';

/**
 * Generates an HTML report from an PHP_CodeCoverage object.
 *
 * @category   PHP
 * @package    CodeCoverage
 * @author     Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @copyright  2009-2010 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version    Release: @package_version@
 * @link       http://github.com/sebastianbergmann/php-code-coverage
 * @since      Class available since Release 1.0.0
 */
class PHP_CodeCoverage_Report_HTML
{
    /**
     * @var string
     */
    public static $templatePath;

    /**
     * @var array
     */
    protected $options;

    /**
     * Constructor.
     *
     * @param array $options
     */
    public function __construct(array $options = array())
    {
        if (!isset($options['title'])) {
            $options['title'] = '';
        }

        if (!isset($options['charset'])) {
            $options['charset'] = 'UTF-8';
        }

        if (!isset($options['yui'])) {
            $options['yui'] = TRUE;
        }

        if (!isset($options['highlight'])) {
            $options['highlight'] = FALSE;
        }

        if (!isset($options['lowUpperBound'])) {
            $options['lowUpperBound'] = 35;
        }

        if (!isset($options['highLowerBound'])) {
            $options['highLowerBound'] = 70;
        }

        $this->options = $options;

        self::$templatePath = sprintf(
          '%s%sHTML%sTemplate%s',

          dirname(__FILE__),
          DIRECTORY_SEPARATOR,
          DIRECTORY_SEPARATOR,
          DIRECTORY_SEPARATOR
        );
    }

    /**
     * @param PHP_CodeCoverage $coverage
     * @param string           $target
     */
    public function process(PHP_CodeCoverage $coverage, $target)
    {
        $target     = PHP_CodeCoverage_Util::getDirectory($target);
        $files      = $coverage->getSummary();
        $commonPath = PHP_CodeCoverage_Util::reducePaths($files);
        $items      = PHP_CodeCoverage_Util::buildDirectoryStructure($files);
        $root       = new PHP_CodeCoverage_Report_HTML_Node_Directory(
                        $commonPath, NULL
                      );

        $this->addItems($root, $items, $files);
        $this->copyFiles($target);

        $this->render($root, $target);
    }

    /**
     * @param PHP_CodeCoverage_Report_HTML_Node_Directory $root
     * @param string                                      $target
     */
    protected function render(PHP_CodeCoverage_Report_HTML_Node_Directory $root, $target)
    {
        $root->render(
          $target,
          $this->options['title'],
          $this->options['charset'],
          $this->options['lowUpperBound'],
          $this->options['highLowerBound']
        );
    }

    /**
     * @param PHP_CodeCoverage_Report_HTML_Node_Directory $root
     * @param array                                       $items
     * @param array                                       $files
     */
    protected function addItems(PHP_CodeCoverage_Report_HTML_Node_Directory $root, array $items, array $files)
    {
        foreach ($items as $key => $value) {
            if (substr($key, -2) == '/f') {
                try {
                    $file = $root->addFile(
                      substr($key, 0, -2),
                      $value,
                      $this->options['yui'],
                      $this->options['highlight']
                    );
                }

                catch (RuntimeException $e) {
                    continue;
                }
            } else {
                $child = $root->addDirectory($key);
                $this->addItems($child, $value, $files);
            }
        }
    }

    /**
     * @param string $target
     */
    protected function copyFiles($target)
    {
        $files = array(
          'butter.png',
          'chameleon.png',
          'close12_1.gif',
          'container.css',
          'container-min.js',
          'glass.png',
          'scarlet_red.png',
          'snow.png',
          'style.css',
          'yahoo-dom-event.js'
        );

        foreach ($files as $file) {
            copy(self::$templatePath . $file, $target . $file);
        }
    }
}
