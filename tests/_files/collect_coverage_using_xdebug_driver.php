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
 * This script collects code coverage data for BankAccount.php using XdebugDriver
 * and prints which kinds of coverage data were collected. It is run in a separate
 * process by XdebugDriverTest because starting and stopping the collection of code
 * coverage data interferes with the collection of code coverage data for this
 * library's own test suite.
 */

use SebastianBergmann\CodeCoverage\Driver\Granularity;
use SebastianBergmann\CodeCoverage\Driver\XdebugDriver;
use SebastianBergmann\CodeCoverage\Filter;

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/BankAccount.php';

$granularity = Granularity::Line;

if ($argv[1] === Granularity::LineAndBranch->name) {
    $granularity = Granularity::LineAndBranch;
} elseif ($argv[1] === Granularity::LineBranchAndPath->name) {
    $granularity = Granularity::LineBranchAndPath;
}

$driver = new XdebugDriver(new Filter);

$driver->setGranularity($granularity);

$driver->start();

new BankAccount()->depositMoney(1);

$data = $driver->stop();

$file     = realpath(__DIR__ . '/BankAccount.php');
$function = 'BankAccount->setBalance';

$lines    = $data->lineCoverage()[$file] ?? [];
$branches = $data->functionCoverage()[$file][$function]['branches'] ?? [];
$paths    = $data->functionCoverage()[$file][$function]['paths'] ?? [];

print json_encode(
    [
        'lines'    => $lines !== [],
        'branches' => $branches !== [],
        'paths'    => $paths !== [],
    ],
);
