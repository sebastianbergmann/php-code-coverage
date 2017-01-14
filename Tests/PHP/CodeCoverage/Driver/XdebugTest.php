<?php

/**
 * Tests for the PHP_CodeCoverage_Driver_Xdebug class.
 *
 * @category   PHP
 * @package    CodeCoverage_Driver
 * @subpackage Tests
 * @author     Fabrice Bernhard <fabriceb@theood.fr>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version    Release: @package_version@
 * @link       http://github.com/sebastianbergmann/php-code-coverage
 */
class PHP_CodeCoverage_Driver_XdebugTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Tests that the Xdebug driver by-passed Xdebug bug #331
	 * http://bugs.xdebug.org/bug_view_page.php?bug_id=0000331
	 *
	 * @covers PHP_CodeCoverage_Driver_Xdebug::cleanFilenames
	 * @covers PHP_CodeCoverage_Driver_Xdebug::stop
     *
	 */
	public function testCoveredFiles()
	{
		$driver = new PHP_CodeCoverage_Driver_Xdebug;
		$driver->start();
        assert('2 >= 0');
		$data = $driver->stop();
		foreach ($data as $file => $lines) {
			$this->assertNotcontains(' : assert code', $file);
		}
	}
}
