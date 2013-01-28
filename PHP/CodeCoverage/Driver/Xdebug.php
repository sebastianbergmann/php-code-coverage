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
 * @since			File available since Release 1.0.0
 */

/**
 * Driver for Xdebug's code coverage functionality.
 *
 * @category	 PHP
 * @package		CodeCoverage
 * @author		 Sebastian Bergmann <sebastian@phpunit.de>
 * @copyright	2009-2013 Sebastian Bergmann <sebastian@phpunit.de>
 * @license		http://www.opensource.org/licenses/BSD-3-Clause	The BSD 3-Clause License
 * @link			 http://github.com/sebastianbergmann/php-code-coverage
 * @since			Class available since Release 1.0.0
 * @codeCoverageIgnore
 */
class PHP_CodeCoverage_Driver_Xdebug implements PHP_CodeCoverage_Driver
{
		/**
		 * Constructor.
		 */
		public function __construct()
		{
				if (!extension_loaded('xdebug')) {
						throw new PHP_CodeCoverage_Exception('Xdebug is not loaded.');
				}

				if (!ini_get('xdebug.coverage_enable')) {
						throw new PHP_CodeCoverage_Exception(
							'You need to set xdebug.coverage_enable=On in your php.ini.'
						);
				}
		}

		/**
		 * Start collection of code coverage information.
		 */
		public function start()
		{
				xdebug_start_code_coverage(XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE);
		}

		/**
		 * Stop collection of code coverage information.
		 *
		 * @return array
		 */
		public function stop()
		{
				$codeCoverage = xdebug_get_code_coverage();
				xdebug_stop_code_coverage();

				return $codeCoverage;
		}
}
