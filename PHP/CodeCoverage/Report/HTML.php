<?php
/**
 * PHP_CodeCoverage
 *
 * Copyright (c) 2009-2011, Sebastian Bergmann <sb@sebastian-bergmann.de>.
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
 * @copyright  2009-2011 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link       http://github.com/sebastianbergmann/php-code-coverage
 * @since      File available since Release 1.0.0
 */

/**
 * Generates an HTML report from an PHP_CodeCoverage object.
 *
 * @category   PHP
 * @package    CodeCoverage
 * @author     Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @copyright  2009-2011 Sebastian Bergmann <sb@sebastian-bergmann.de>
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
     * @var string
     */
    protected $title;

    /**
     * @var boolean
     */
    protected $yui;

    /**
     * Constructor.
     *
     * @param array $options
     */
    public function __construct($title = '', $charset = 'UTF-8', $yui = TRUE, $highlight = FALSE, $lowUpperBound = 35, $highLowerBound = 70, $generator = '')
    {
        $this->charset        = $charset;
        $this->generator      = $generator;
        $this->highLowerBound = $highLowerBound;
        $this->highlight      = $highlight;
        $this->lowUpperBound  = $lowUpperBound;
        $this->title          = $title;
        $this->yui            = $yui;

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
        $target = PHP_CodeCoverage_Util::getDirectory($target);
        $report = $coverage->getReport();
        unset($coverage);

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
          $this->highlight,
          $this->yui
        );

        $dashboard->render(
          $report, $target . 'index.dashboard.html', $this->title
        );

        $directory->render($report, $target . 'index.html', $this->title);

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
        $files = array(
          'close12_1.gif',
          'container.css',
          'container-min.js',
          'directory.png',
          'file.png',
          'glass.png',
          'highcharts.js',
          'jquery.min.js',
          'style.css',
          'yahoo-dom-event.js'
        );

        foreach ($files as $file) {
            copy($this->templatePath . $file, $target . $file);
        }
    }
}
