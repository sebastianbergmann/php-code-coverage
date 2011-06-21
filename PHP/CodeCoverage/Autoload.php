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
 * @since      File available since Release 1.1.0
 */

require_once 'File/Iterator/Autoload.php';
require_once 'PHP/Token/Stream/Autoload.php';
require_once 'Text/Template/Autoload.php';

function php_codecoverage_autoload($class = NULL) {
    static $classes = NULL;
    static $path = NULL;

    if ($classes === NULL) {
        $classes = array(
          'php_codecoverage' => '/CodeCoverage.php',
          'php_codecoverage_driver' => '/CodeCoverage/Driver.php',
          'php_codecoverage_driver_xdebug' => '/CodeCoverage/Driver/Xdebug.php',
          'php_codecoverage_exception' => '/CodeCoverage/Exception.php',
          'php_codecoverage_filter' => '/CodeCoverage/Filter.php',
          'php_codecoverage_report_clover' => '/CodeCoverage/Report/Clover.php',
          'php_codecoverage_report_html' => '/CodeCoverage/Report/HTML.php',
          'php_codecoverage_report_html_node' => '/CodeCoverage/Report/HTML/Node.php',
          'php_codecoverage_report_html_node_directory' => '/CodeCoverage/Report/HTML/Node/Directory.php',
          'php_codecoverage_report_html_node_file' => '/CodeCoverage/Report/HTML/Node/File.php',
          'php_codecoverage_report_html_node_iterator' => '/CodeCoverage/Report/HTML/Node/Iterator.php',
          'php_codecoverage_report_php' => '/CodeCoverage/Report/PHP.php',
          'php_codecoverage_textui_command' => '/CodeCoverage/TextUI/Command.php',
          'php_codecoverage_util' => '/CodeCoverage/Util.php'
        );

        $path = dirname(dirname(__FILE__));
    }

    if ($class === NULL) {
        $result = array();

        foreach ($classes as $file) {
            $result[] = $path . $file;
        }

        return $result;
    }

    $cn = strtolower($class);

    if (isset($classes[$cn])) {
        require $path . $classes[$cn];
    }
}

spl_autoload_register('php_codecoverage_autoload');

require_once 'ezc/Base/base.php';
spl_autoload_register(array('ezcBase', 'autoload'));
