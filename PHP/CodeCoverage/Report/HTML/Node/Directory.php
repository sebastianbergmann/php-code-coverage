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

require_once 'PHP/CodeCoverage/Report/HTML/Node/Iterator.php';

/**
 * Represents a directory in the code coverage information tree.
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
class PHP_CodeCoverage_Report_HTML_Node_Directory extends PHP_CodeCoverage_Report_HTML_Node implements IteratorAggregate
{
    /**
     * @var PHP_CodeCoverage_Report_HTML_Node[]
     */
    protected $children = array();

    /**
     * @var PHP_CodeCoverage_Report_HTML_Node_Directory[]
     */
    protected $directories = array();

    /**
     * @var PHP_CodeCoverage_Report_HTML_Node_File[]
     */
    protected $files = array();

    /**
     * @var array
     */
    protected $classes;

    /**
     * @var integer
     */
    protected $numExecutableLines = -1;

    /**
     * @var integer
     */
    protected $numExecutedLines = -1;

    /**
     * @var integer
     */
    protected $numClasses = -1;

    /**
     * @var integer
     */
    protected $numTestedClasses = -1;

    /**
     * @var integer
     */
    protected $numMethods = -1;

    /**
     * @var integer
     */
    protected $numTestedMethods = -1;

    /**
     * Returns an iterator for this node.
     *
     * @return RecursiveIteratorIterator
     */
    public function getIterator()
    {
        return new RecursiveIteratorIterator(
          new PHP_CodeCoverage_Report_HTML_Node_Iterator($this),
          RecursiveIteratorIterator::SELF_FIRST
        );
    }

    /**
     * Adds a new directory.
     *
     * @return PHP_CodeCoverage_Report_HTML_Node_Directory
     */
    public function addDirectory($name)
    {
        $directory = new PHP_CodeCoverage_Report_HTML_Node_Directory(
          $name, $this
        );

        $this->children[]    = $directory;
        $this->directories[] = &$this->children[count($this->children) - 1];

        return $directory;
    }

    /**
     * Adds a new file.
     *
     * @param  string  $name
     * @param  array   $lines
     * @param  boolean $yui
     * @param  boolean $highlight
     * @return PHP_CodeCoverage_Report_HTML_Node_File
     * @throws RuntimeException
     */
    public function addFile($name, array $lines, $yui, $highlight)
    {
        $file = new PHP_CodeCoverage_Report_HTML_Node_File(
          $name, $this, $lines, $yui, $highlight
        );

        $this->children[] = $file;
        $this->files[]    = &$this->children[count($this->children) - 1];

        $this->numExecutableLines = -1;
        $this->numExecutedLines   = -1;

        return $file;
    }

    /**
     * Returns the directories in this directory.
     *
     * @return array
     */
    public function getDirectories()
    {
        return $this->directories;
    }

    /**
     * Returns the files in this directory.
     *
     * @return array
     */
    public function getFiles()
    {
        return $this->files;
    }

    /**
     * Returns the child nodes of this node.
     *
     * @return array
     */
    public function getChildNodes()
    {
        return $this->children;
    }

    /**
     * Returns the classes of this node.
     *
     * @return array
     */
    public function getClasses()
    {
        if ($this->classes === NULL) {
            $this->classes = array();

            foreach ($this->children as $child) {
                $this->classes = array_merge(
                  $this->classes, $child->getClasses()
                );
            }
        }

        return $this->classes;
    }

    /**
     * Returns the number of executable lines.
     *
     * @return integer
     */
    public function getNumExecutableLines()
    {
        if ($this->numExecutableLines == -1) {
            $this->numExecutableLines = 0;

            foreach ($this->children as $child) {
                $this->numExecutableLines += $child->getNumExecutableLines();
            }
        }

        return $this->numExecutableLines;
    }

    /**
     * Returns the number of executed lines.
     *
     * @return integer
     */
    public function getNumExecutedLines()
    {
        if ($this->numExecutedLines == -1) {
            $this->numExecutedLines = 0;

            foreach ($this->children as $child) {
                $this->numExecutedLines += $child->getNumExecutedLines();
            }
        }

        return $this->numExecutedLines;
    }

    /**
     * Returns the number of classes.
     *
     * @return integer
     */
    public function getNumClasses()
    {
        if ($this->numClasses == -1) {
            $this->numClasses = 0;

            foreach ($this->children as $child) {
                $this->numClasses += $child->getNumClasses();
            }
        }

        return $this->numClasses;
    }

    /**
     * Returns the number of tested classes.
     *
     * @return integer
     */
    public function getNumTestedClasses()
    {
        if ($this->numTestedClasses == -1) {
            $this->numTestedClasses = 0;

            foreach ($this->children as $child) {
                $this->numTestedClasses += $child->getNumTestedClasses();
            }
        }

        return $this->numTestedClasses;
    }

    /**
     * Returns the number of methods.
     *
     * @return integer
     */
    public function getNumMethods()
    {
        if ($this->numMethods == -1) {
            $this->numMethods = 0;

            foreach ($this->children as $child) {
                $this->numMethods += $child->getNumMethods();
            }
        }

        return $this->numMethods;
    }

    /**
     * Returns the number of tested methods.
     *
     * @return integer
     */
    public function getNumTestedMethods()
    {
        if ($this->numTestedMethods == -1) {
            $this->numTestedMethods = 0;

            foreach ($this->children as $child) {
                $this->numTestedMethods += $child->getNumTestedMethods();
            }
        }

        return $this->numTestedMethods;
    }

    /**
     * Renders this node.
     *
     * @param string  $target
     * @param string  $title
     * @param string  $charset
     * @param integer $lowUpperBound
     * @param integer $highLowerBound
     * @param string  $generator
     */
    public function render($target, $title, $charset = 'UTF-8', $lowUpperBound = 35, $highLowerBound = 70, $generator = '')
    {
        $this->doRender(
          $target, $title, $charset, $lowUpperBound, $highLowerBound, $generator
        );

        foreach ($this->children as $child) {
            $child->render(
              $target,
              $title,
              $charset,
              $lowUpperBound,
              $highLowerBound,
              $generator
            );
        }

        $this->children = array();
    }

    /**
     * @param string  $target
     * @param string  $title
     * @param string  $charset
     * @param integer $lowUpperBound
     * @param integer $highLowerBound
     * @param string  $generator
     */
    protected function doRender($target, $title, $charset, $lowUpperBound, $highLowerBound, $generator)
    {
        $cleanId = PHP_CodeCoverage_Util::getSafeFilename($this->getId());
        $file    = $target . $cleanId . '.html';

        $template = new Text_Template(
          PHP_CodeCoverage_Report_HTML::$templatePath . 'directory.html'
        );

        $this->setTemplateVars($template, $title, $charset, $generator);

        $template->setVar(
          array(
            'total_item'       => $this->renderTotalItem(
                                    $lowUpperBound, $highLowerBound
                                  ),
            'items'            => $this->renderItems(
                                    $lowUpperBound, $highLowerBound
                                  ),
            'low_upper_bound'  => $lowUpperBound,
            'high_lower_bound' => $highLowerBound
          )
        );

        $template->renderTo($file);

        $this->directories = array();
        $this->files       = array();
    }

    /**
     * @param  float  $lowUpperBound
     * @param  float  $highLowerBound
     * @return string
     */
    protected function renderItems($lowUpperBound, $highLowerBound)
    {
        $items = $this->doRenderItems(
          $this->directories, $lowUpperBound, $highLowerBound, 'coverDirectory'
        );

        $items .= $this->doRenderItems(
          $this->files, $lowUpperBound, $highLowerBound, 'coverFile'
        );

        return $items;
    }

    /**
     * @param  array  $items
     * @param  float  $lowUpperBound
     * @param  float  $highLowerBound
     * @param  string $itemClass
     * @return string
     */
    protected function doRenderItems(array $items, $lowUpperBound, $highLowerBound, $itemClass)
    {
        $result = '';

        foreach ($items as $item) {
            $result .= $this->doRenderItemObject(
              $item, $lowUpperBound, $highLowerBound, NULL, $itemClass
            );
        }

        return $result;
    }
}
