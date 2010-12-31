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
 * Base class for nodes in the code coverage information tree.
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
abstract class PHP_CodeCoverage_Report_HTML_Node
{
    /**
     * @var array
     */
    protected $cache = array();

    /**
     * @var string
     */
    protected $name;

    /**
     * @var PHP_CodeCoverage_Report_HTML_Node
     */
    protected $parent;

    /**
     * Constructor.
     *
     * @param string                            $name
     * @param PHP_CodeCoverage_Report_HTML_Node $parent
     */
    public function __construct($name, PHP_CodeCoverage_Report_HTML_Node $parent = NULL)
    {
        $this->name   = $name;
        $this->parent = $parent;
    }

    /**
     * Returns the percentage of classes that has been tested.
     *
     * @return integer
     */
    public function getTestedClassesPercent()
    {
        return PHP_CodeCoverage_Util::percent(
          $this->getNumTestedClasses(),
          $this->getNumClasses(),
          TRUE
        );
    }

    /**
     * Returns the percentage of methods that has been tested.
     *
     * @return integer
     */
    public function getTestedMethodsPercent()
    {
        return PHP_CodeCoverage_Util::percent(
          $this->getNumTestedMethods(),
          $this->getNumMethods(),
          TRUE
        );
    }

    /**
     * Returns the percentage of executed lines.
     *
     * @return integer
     */
    public function getLineExecutedPercent()
    {
        return PHP_CodeCoverage_Util::percent(
          $this->getNumExecutedLines(),
          $this->getNumExecutableLines(),
          TRUE
        );
    }

    /**
     * Returns this node's ID.
     *
     * @return string
     */
    public function getId()
    {
        if (!isset($this->cache['id'])) {
            if ($this->parent === NULL) {
                $this->cache['id'] = 'index';
            } else {
                $parentId = $this->parent->getId();

                if ($parentId == 'index') {
                    $this->cache['id'] = $this->getName();
                } else {
                    $this->cache['id'] = $parentId . '_' . $this->getName();
                }
            }
        }

        return $this->cache['id'];
    }

    /**
     * Returns this node's name.
     *
     * @param  boolean $includeParent
     * @return string
     */
    public function getName($includeParent = FALSE, $includeCommonPath = FALSE)
    {
        if ($includeParent && $this->parent !== NULL) {
            if (!isset($this->cache['nameIncludingParent'])) {
                $parent = $this->parent->getName(TRUE);

                if (!empty($parent)) {
                    $this->cache['nameIncludingParent'] = $parent . '/' .
                                                          $this->name;
                } else {
                    $this->cache['nameIncludingParent'] = $this->name;
                }
            }

            return $this->cache['nameIncludingParent'];
        } else {
            if ($this->parent !== NULL) {
                return $this->name;
            } else {
                return $includeCommonPath ? $this->name : '';
            }
        }
    }

    /**
     * Returns the link to this node.
     *
     * @param  boolean $full
     * @return string
     */
    public function getLink($full)
    {
        if (substr($this->name, -1) == DIRECTORY_SEPARATOR) {
            $name = substr($this->name, 0, -1);
        } else {
            $name = $this->name;
        }

        $cleanId = PHP_CodeCoverage_Util::getSafeFilename($this->getId());

        if ($full) {
            if ($this->parent !== NULL) {
                $parent = $this->parent->getLink(TRUE) . DIRECTORY_SEPARATOR;
            } else {
                $parent = '';
            }

            return sprintf(
              '%s<a href="%s.html">%s</a>',
              $parent,
              $cleanId,
              $name
            );
        } else {
            return sprintf(
              '<a href="%s.html">%s</a>',
              $cleanId,
              $name
            );
        }
    }

    /**
     * Returns this node's path.
     *
     * @return string
     */
    public function getPath()
    {
        if (!isset($this->cache['path'])) {
            if ($this->parent === NULL) {
                $this->cache['path'] = $this->getName(FALSE, TRUE);
            } else {
                $parentPath = $this->parent->getPath();

                if (substr($parentPath, -1) == DIRECTORY_SEPARATOR) {
                    $this->cache['path'] = $parentPath .
                                           $this->getName(FALSE, TRUE);
                } else {
                    $this->cache['path'] = $parentPath .
                                           DIRECTORY_SEPARATOR .
                                           $this->getName(FALSE, TRUE);

                    if ($parentPath === '' &&
                        realpath($this->cache['path']) === FALSE &&
                        realpath($this->getName(FALSE, TRUE)) !== FALSE) {
                        $this->cache['path'] = $this->getName(FALSE, TRUE);
                    }
                }
            }
        }

        return $this->cache['path'];
    }

    protected function doRenderItemObject(PHP_CodeCoverage_Report_HTML_Node $item, $lowUpperBound, $highLowerBound, $link = NULL, $itemClass = 'coverItem')
    {
        return $this->doRenderItem(
          array(
            'name'                 => $link != NULL ? $link : $item->getLink(
                                                                FALSE
                                                              ),
            'itemClass'            => $itemClass,
            'numClasses'           => $item->getNumClasses(),
            'numTestedClasses'     => $item->getNumTestedClasses(),
            'testedClassesPercent' => $item->getTestedClassesPercent(),
            'numMethods'           => $item->getNumMethods(),
            'numTestedMethods'     => $item->getNumTestedMethods(),
            'testedMethodsPercent' => $item->getTestedMethodsPercent(),
            'numExecutableLines'   => $item->getNumExecutableLines(),
            'numExecutedLines'     => $item->getNumExecutedLines(),
            'executedLinesPercent' => $item->getLineExecutedPercent(),
            'crap'                 => $link == 'Total' ? '<acronym title="Change Risk Anti-Patterns (CRAP) Index">CRAP</acronym>' : ''
          ),
          $lowUpperBound,
          $highLowerBound
        );
    }

    protected function doRenderItem(array $data, $lowUpperBound, $highLowerBound, $template = NULL)
    {
        if ($template === NULL) {
            if ($this instanceof PHP_CodeCoverage_Report_HTML_Node_Directory) {
                $template = 'directory_item.html';
            } else {
                $template = 'file_item.html';
            }
        }

        $itemTemplate = new Text_Template(
          PHP_CodeCoverage_Report_HTML::$templatePath . $template
        );

        if ($data['numClasses'] > 0) {
            list($classesColor, $classesLevel) = $this->getColorLevel(
              $data['testedClassesPercent'], $lowUpperBound, $highLowerBound
            );

            $classesNumber = $data['numTestedClasses'] . ' / ' .
                             $data['numClasses'];
        } else {
            $classesColor  = 'snow';
            $classesLevel  = 'None';
            $classesNumber = '&nbsp;';
        }

        if ($data['numMethods'] > 0) {
            list($methodsColor, $methodsLevel) = $this->getColorLevel(
              $data['testedMethodsPercent'], $lowUpperBound, $highLowerBound
            );

            $methodsNumber = $data['numTestedMethods'] . ' / ' .
                             $data['numMethods'];
        } else {
            $methodsColor  = 'snow';
            $methodsLevel  = 'None';
            $methodsNumber = '&nbsp;';
        }

        list($linesColor, $linesLevel) = $this->getColorLevel(
          $data['executedLinesPercent'], $lowUpperBound, $highLowerBound
        );

        if ($data['name'] == '<b><a href="#0">*</a></b>') {
            $functions = TRUE;
        } else {
            $functions = FALSE;
        }

        $icon = '';

        if (isset($data['itemClass'])) {
            if ($data['itemClass'] == 'coverDirectory') {
                $icon = '<img alt="directory" src="directory.png"/> ';
            }

            else if ($data['itemClass'] == 'coverFile') {
                $icon = '<img alt="file" src="file.png"/> ';
            }
        }

        $itemTemplate->setVar(
          array(
            'name'                     => $functions ? 'Functions' : $data['name'],
            'icon'                     => $icon,
            'itemClass'                => isset($data['itemClass']) ? $data['itemClass'] : 'coverItem',
            'classes_color'            => $classesColor,
            'classes_level'            => $functions ? 'None' : $classesLevel,
            'classes_tested_width'     => floor($data['testedClassesPercent']),
            'classes_tested_percent'   => !$functions && $data['numClasses'] > 0 ? $data['testedClassesPercent'] . '%' : '&nbsp;',
            'classes_not_tested_width' => 100 - floor($data['testedClassesPercent']),
            'classes_number'           => $functions ? '&nbsp;' : $classesNumber,
            'methods_color'            => $methodsColor,
            'methods_level'            => $methodsLevel,
            'methods_tested_width'     => floor($data['testedMethodsPercent']),
            'methods_tested_percent'   => $data['numMethods'] > 0 ? $data['testedMethodsPercent'] . '%' : '&nbsp;',
            'methods_not_tested_width' => 100 - floor($data['testedMethodsPercent']),
            'methods_number'           => $methodsNumber,
            'lines_color'              => $linesColor,
            'lines_level'              => $linesLevel,
            'lines_executed_width'     => floor($data['executedLinesPercent']),
            'lines_executed_percent'   => $data['executedLinesPercent'] . '%',
            'lines_not_executed_width' => 100 - floor($data['executedLinesPercent']),
            'num_executable_lines'     => $data['numExecutableLines'],
            'num_executed_lines'       => $data['numExecutedLines'],
            'crap'                     => isset($data['crap']) ? $data['crap'] : ''
          )
        );

        return $itemTemplate->render();
    }

    protected function getColorLevel($percent, $lowUpperBound, $highLowerBound)
    {
        $floorPercent = floor($percent);

        if ($floorPercent < $lowUpperBound) {
            $color = 'scarlet_red';
            $level = 'Lo';
        }

        else if ($floorPercent >= $lowUpperBound &&
                 $floorPercent <  $highLowerBound) {
            $color = 'butter';
            $level = 'Med';
        }

        else {
            $color = 'chameleon';
            $level = 'Hi';
        }

        return array($color, $level);
    }

    protected function renderTotalItem($lowUpperBound, $highLowerBound, $directory = TRUE)
    {
        if ($directory &&
            empty($this->directories) &&
            count($this->files) == 1) {
            return '';
        }

        return $this->doRenderItemObject(
                 $this, $lowUpperBound, $highLowerBound, 'Total'
               ) .
               "        <tr>\n" .
               '          <td class="tableHead" colspan="11">&nbsp;</td>' .
               "\n        </tr>\n";
    }

    /**
     * @param Text_Template $template
     * @param string        $title
     * @param string        $charset
     * @param string        $generator
     */
    protected function setTemplateVars(Text_Template $template, $title, $charset, $generator)
    {
        $dashboard = '';

        if ($this instanceof PHP_CodeCoverage_Report_HTML_Node_Directory) {
            $dashboard = sprintf(
              '<a href="%s">dashboard</a>',
              PHP_CodeCoverage_Util::getSafeFilename(
                $this->getId()
              ) . '.dashboard.html'
            );
        }

        $template->setVar(
          array(
            'title'                  => $title,
            'charset'                => $charset,
            'link'                   => $this->getLink(TRUE),
            'dashboard_link'         => $dashboard,
            'num_executable_lines'   => $this->getNumExecutableLines(),
            'num_executed_lines'     => $this->getNumExecutedLines(),
            'lines_executed_percent' => $this->getLineExecutedPercent(),
            'date'                   => date(
                                          'D M j G:i:s T Y',
                                          $_SERVER['REQUEST_TIME']
                                        ),
            'version'                => '@package_version@',
            'php_version'            => PHP_VERSION,
            'generator'              => $generator
          )
        );
    }

    /**
     * Returns the classes of this node.
     *
     * @return array
     */
    abstract public function getClasses();

    /**
     * Returns the number of executable lines.
     *
     * @return integer
     */
    abstract public function getNumExecutableLines();

    /**
     * Returns the number of executed lines.
     *
     * @return integer
     */
    abstract public function getNumExecutedLines();

    /**
     * Returns the number of classes.
     *
     * @return integer
     */
    abstract public function getNumClasses();

    /**
     * Returns the number of tested classes.
     *
     * @return integer
     */
    abstract public function getNumTestedClasses();

    /**
     * Returns the number of methods.
     *
     * @return integer
     */
    abstract public function getNumMethods();

    /**
     * Returns the number of tested methods.
     *
     * @return integer
     */
    abstract public function getNumTestedMethods();

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
    abstract public function render($target, $title, $charset = 'UTF-8', $lowUpperBound = 35, $highLowerBound = 70, $generator = '');
}

require_once 'PHP/CodeCoverage/Report/HTML/Node/Directory.php';
require_once 'PHP/CodeCoverage/Report/HTML/Node/File.php';
