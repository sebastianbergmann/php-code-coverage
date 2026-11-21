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
 * This script collects code coverage data using XdebugDriver and a filter that
 * includes files, optionally after a file that is not included was loaded from
 * a directory that an included file is in. It prints the test fixtures for which the driver reported
 * data as well as the test fixtures for which data is left after the filter was
 * applied. It is run in a separate process by XdebugDriverTest because starting
 * and stopping the collection of code coverage data interferes with the
 * collection of code coverage data for this library's own test suite.
 */

use SebastianBergmann\CodeCoverage\Driver\XdebugDriver;
use SebastianBergmann\CodeCoverage\Filter;
use SebastianBergmann\CodeCoverage\FilterProcessor;

require __DIR__ . '/../../vendor/autoload.php';

$filter = new Filter;

$filter->includeFile(__DIR__ . '/BankAccount.php');
$filter->includeFile(__DIR__ . '/filter-directories/src/a.php');

if ($argv[1] === 'file-that-is-not-included-is-loaded-before') {
    require __DIR__ . '/filter-directories/src/loaded_before.php';
}

$driver = new XdebugDriver($filter);

$driver->start();

require __DIR__ . '/BankAccount.php';

require __DIR__ . '/filter-directories/src/a.php';

require __DIR__ . '/filter-directories/src/b.php';

require __DIR__ . '/filter-directories/src-old/c.php';

$data = $driver->stop();

$files = static function () use ($data): array
{
    $files = [];

    foreach (array_keys($data->lineCoverage()) as $file) {
        if ($file === __FILE__ || !str_starts_with($file, __DIR__ . DIRECTORY_SEPARATOR)) {
            continue;
        }

        $files[] = str_replace([__DIR__ . DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR], ['', '/'], $file);
    }

    sort($files);

    return $files;
};

$reported = $files();

(new FilterProcessor)->applyFilter($data, $filter);

print json_encode(
    [
        'reported' => $reported,
        'filtered' => $files(),
    ],
);
