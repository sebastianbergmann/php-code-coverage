<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*
 * This script collects code coverage data using XdebugDriver for a class that is
 * compiled before the collection of code coverage data is started and prints the
 * line coverage data for the file that declares that class. It is run in a separate
 * process by CodeCoverageTest because starting and stopping the collection of code
 * coverage data interferes with the collection of code coverage data for this
 * library's own test suite.
 */

use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Driver\XdebugDriver;
use SebastianBergmann\CodeCoverage\Filter;
use SebastianBergmann\CodeCoverage\TestFixture\ClassWithTwoMethods;

require __DIR__ . '/../../vendor/autoload.php';

$file = realpath(__DIR__ . '/source_with_two_methods.php');

$filter = new Filter;
$filter->includeFile($file);

$coverage = new CodeCoverage(new XdebugDriver($filter), $filter);

require $file;

$coverage->start('test');

new ClassWithTwoMethods()->one();

$coverage->stop();

print json_encode($coverage->getData()->lineCoverage()[$file] ?? []);
