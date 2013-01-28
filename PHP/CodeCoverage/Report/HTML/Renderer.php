<?php
/**
 * PHP_CodeCoverage
 *
 * Copyright (c) 2009-2013, Sebastian Bergmann <sebastian@phpunit.de>.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *	 * Redistributions of source code must retain the above copyright
 *		 notice, this list of conditions and the following disclaimer.
 *
 *	 * Redistributions in binary form must reproduce the above copyright
 *		 notice, this list of conditions and the following disclaimer in
 *		 the documentation and/or other materials provided with the
 *		 distribution.
 *
 *	 * Neither the name of Sebastian Bergmann nor the names of his
 *		 contributors may be used to endorse or promote products derived
 *		 from this software without specific prior written permission.
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
 * @category	 PHP
 * @package		CodeCoverage
 * @author		 Sebastian Bergmann <sebastian@phpunit.de>
 * @copyright	2009-2013 Sebastian Bergmann <sebastian@phpunit.de>
 * @license		http://www.opensource.org/licenses/BSD-3-Clause	The BSD 3-Clause License
 * @link			 http://github.com/sebastianbergmann/php-code-coverage
 * @since			File available since Release 1.1.0
 */

/**
 * Base class for PHP_CodeCoverage_Report_Node renderers.
 *
 * @category	 PHP
 * @package		CodeCoverage
 * @author		 Sebastian Bergmann <sebastian@phpunit.de>
 * @copyright	2009-2013 Sebastian Bergmann <sebastian@phpunit.de>
 * @license		http://www.opensource.org/licenses/BSD-3-Clause	The BSD 3-Clause License
 * @link			 http://github.com/sebastianbergmann/php-code-coverage
 * @since			Class available since Release 1.1.0
 */
abstract class PHP_CodeCoverage_Report_HTML_Renderer
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
		 * @var string
		 */
		protected $date;

		/**
		 * @var integer
		 */
		protected $lowUpperBound;

		/**
		 * @var integer
		 */
		protected $highLowerBound;

		/**
		 * @var string
		 */
		protected $version;

		/**
		 * Constructor.
		 *
		 * @param string	$templatePath
		 * @param string	$charset
		 * @param string	$generator
		 * @param string	$date
		 * @param integer $lowUpperBound
		 * @param integer $highLowerBound
		 */
		public function __construct($templatePath, $charset, $generator, $date, $lowUpperBound, $highLowerBound)
		{
				$version = new SebastianBergmann\Version('1.3', __DIR__);

				$this->templatePath	 = $templatePath;
				$this->charset				= $charset;
				$this->generator			= $generator;
				$this->date					 = $date;
				$this->lowUpperBound	= $lowUpperBound;
				$this->highLowerBound = $highLowerBound;
				$this->version				= $version->getVersion();
		}

		/**
		 * @param	Text_Template $template
		 * @param	array				 $data
		 * @return string
		 */
		protected function renderItemTemplate(Text_Template $template, array $data)
		{
				$classesBar		= '&nbsp;';
				$classesLevel	= 'None';
				$classesNumber = '&nbsp;';

				if (isset($data['numClasses']) && $data['numClasses'] > 0) {
						$classesLevel = $this->getColorLevel($data['testedClassesPercent']);

						$classesNumber = $data['numTestedClasses'] . ' / ' .
														 $data['numClasses'];

						$classesBar = $this->getCoverageBar(
							$data['testedClassesPercent']
						);
				}

				$methodsBar		= '&nbsp;';
				$methodsLevel	= 'None';
				$methodsNumber = '&nbsp;';

				if ($data['numMethods'] > 0) {
						$methodsLevel = $this->getColorLevel($data['testedMethodsPercent']);

						$methodsNumber = $data['numTestedMethods'] . ' / ' .
														 $data['numMethods'];

						$methodsBar = $this->getCoverageBar(
							$data['testedMethodsPercent']
						);
				}

				$linesBar		= '&nbsp;';
				$linesLevel	= 'None';
				$linesNumber = '&nbsp;';

				if ($data['numExecutableLines'] > 0) {
						$linesLevel = $this->getColorLevel($data['linesExecutedPercent']);

						$linesNumber = $data['numExecutedLines'] . ' / ' .
													 $data['numExecutableLines'];

						$linesBar = $this->getCoverageBar(
							$data['linesExecutedPercent']
						);
				}

				$template->setVar(
					array(
						'icon' => isset($data['icon']) ? $data['icon'] : '',
						'crap' => isset($data['crap']) ? $data['crap'] : '',
						'name' => $data['name'],
						'lines_bar' => $linesBar,
						'lines_executed_percent' => $data['linesExecutedPercentAsString'],
						'lines_level' => $linesLevel,
						'lines_number' => $linesNumber,
						'methods_bar' => $methodsBar,
						'methods_tested_percent' => $data['testedMethodsPercentAsString'],
						'methods_level' => $methodsLevel,
						'methods_number' => $methodsNumber,
						'classes_bar' => $classesBar,
						'classes_tested_percent' => isset($data['testedClassesPercentAsString']) ? $data['testedClassesPercentAsString'] : '',
						'classes_level' => $classesLevel,
						'classes_number' => $classesNumber
					)
				);

				return $template->render();
		}

		/**
		 * @param Text_Template								$template
		 * @param PHP_CodeCoverage_Report_Node $node
		 */
		protected function setCommonTemplateVariables(Text_Template $template, PHP_CodeCoverage_Report_Node $node)
		{
				$template->setVar(
					array(
						'id'							 => $node->getId(),
						'full_path'				=> $node->getPath(),
						'breadcrumbs'			=> $this->getBreadcrumbs($node),
						'charset'					=> $this->charset,
						'date'						 => $this->date,
						'version'					=> $this->version,
						'php_version'			=> PHP_VERSION,
						'generator'				=> $this->generator,
						'low_upper_bound'	=> $this->lowUpperBound,
						'high_lower_bound' => $this->highLowerBound
					)
				);
		}

		protected function getBreadcrumbs(PHP_CodeCoverage_Report_Node $node)
		{
				$breadcrumbs = '';

				$path = $node->getPathAsArray();

				foreach ($path as $step) {
						if ($step !== $node) {
								$breadcrumbs .= sprintf(
									'				<li><a href="%s.html">%s</a> <span class="divider">/</span></li>' . "\n",
									$step->getId(),
									$step->getName()
								);
						} else {
								$breadcrumbs .= sprintf(
									'				<li class="active">%s</li>' . "\n",
									$step->getName()
								);

								if ($node instanceof PHP_CodeCoverage_Report_Node_Directory) {
										$breadcrumbs .= sprintf(
											'				<li>(<a href="%s.dashboard.html">Dashboard</a>)</li>' . "\n",
											$step->getId()
										);
								}
						}
				}

				return $breadcrumbs;
		}

		protected function getCoverageBar($percent)
		{
				$level = $this->getColorLevel($percent);

				$template = new Text_Template(
					$this->templatePath . 'coverage_bar.html'
				);

				$template->setVar(array('level' => $level, 'percent' => sprintf("%.2F", $percent)));

				return $template->render();
		}

		/**
		 * @param	integer $percent
		 * @return string
		 */
		protected function getColorLevel($percent)
		{
				if ($percent < $this->lowUpperBound) {
						return 'danger';
				}

				else if ($percent >= $this->lowUpperBound &&
								 $percent <	$this->highLowerBound) {
						return 'warning';
				}

				else {
						return 'success';
				}
		}
}
