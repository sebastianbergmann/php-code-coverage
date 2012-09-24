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
 * Generates an HTML report from an PHP_CodeCoverage object.
 *
 * @category   PHP
 * @package    CodeCoverage
 * @author     Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @copyright  2009-2012 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @link       http://github.com/sebastianbergmann/php-code-coverage
 * @since      Class available since Release 1.0.0
 */
class PHP_CodeCoverage_Report_HTML
{
    /**
     * @var string
     */
    protected $templatePath;

    /**
     * @var string
     */
    protected $charset;

    /**
     * @var string
     */
    protected $generator;

    /**
     * @var integer
     */
    protected $lowUpperBound;

    /**
     * @var integer
     */
    protected $highLowerBound;

    /**
     * @var boolean
     */
    protected $highlight;

    /**
     * Constructor.
     *
     * @param array $options
     */
    public function __construct($charset = 'UTF-8', $highlight = FALSE, $lowUpperBound = 35, $highLowerBound = 70, $generator = '')
    {
        $this->charset        = $charset;
        $this->generator      = $generator;
        $this->highLowerBound = $highLowerBound;
        $this->highlight      = $highlight;
        $this->lowUpperBound  = $lowUpperBound;

        $this->templatePath = sprintf(
          '%s%sHTML%sRenderer%sTemplate%s',

          dirname(__FILE__),
          DIRECTORY_SEPARATOR,
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
        $target = $this->getDirectory($target);
        $report = $coverage->getReport();
        unset($coverage);

        if (!isset($_SERVER['REQUEST_TIME'])) {
            $_SERVER['REQUEST_TIME'] = time();
        }

        $date = date('D M j G:i:s T Y', $_SERVER['REQUEST_TIME']);

        $dashboard = new PHP_CodeCoverage_Report_HTML_Renderer_Dashboard(
          $this->templatePath,
          $this->charset,
          $this->generator,
          $date,
          $this->lowUpperBound,
          $this->highLowerBound
        );

        $directory = new PHP_CodeCoverage_Report_HTML_Renderer_Directory(
          $this->templatePath,
          $this->charset,
          $this->generator,
          $date,
          $this->lowUpperBound,
          $this->highLowerBound
        );

        $file = new PHP_CodeCoverage_Report_HTML_Renderer_File(
          $this->templatePath,
          $this->charset,
          $this->generator,
          $date,
          $this->lowUpperBound,
          $this->highLowerBound,
          $this->highlight
        );

        $dashboard->render($report, $target . 'index.dashboard.html');
        $directory->render($report, $target . 'index.html');

        foreach ($report as $node) {
            $id = $node->getId();

            if ($node instanceof PHP_CodeCoverage_Report_Node_Directory) {
                $dashboard->render($node, $target . $id . '.dashboard.html');
                $directory->render($node, $target . $id . '.html');
            } else {
                $file->render($node, $target . $id . '.html');
            }
        }

        $this->copyFiles($target);
    }

    /**
     * @param string $target
     */
    protected function copyFiles($target)
    {
        $dir = $this->getDirectory($target . 'css');
        copy($this->templatePath . 'css/bootstrap.min.css', $dir . 'bootstrap.min.css');
        copy($this->templatePath . 'css/bootstrap-responsive.min.css', $dir . 'bootstrap-responsive.min.css');
        copy($this->templatePath . 'css/style.css', $dir . 'style.css');

        $dir = $this->getDirectory($target . 'js');
        copy($this->templatePath . 'js/bootstrap.min.js', $dir . 'bootstrap.min.js');
        copy($this->templatePath . 'js/highcharts.js', $dir . 'highcharts.js');
        copy($this->templatePath . 'js/jquery.min.js', $dir . 'jquery.min.js');

        $dir = $this->getDirectory($target . 'img');
        copy($this->templatePath . 'img/glyphicons-halflings.png', $dir . 'glyphicons-halflings.png');
        copy($this->templatePath . 'img/glyphicons-halflings-white.png', $dir . 'glyphicons-halflings-white.png');
    }

    /**
     * @param  string $directory
     * @return string
     * @throws PHP_CodeCoverage_Exception
     * @since  Method available since Release 1.2.0
     */
    protected function getDirectory($directory)
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
}
