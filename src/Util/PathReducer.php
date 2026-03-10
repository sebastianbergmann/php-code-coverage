<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\Util;

use const DIRECTORY_SEPARATOR;
use function array_shift;
use function basename;
use function count;
use function dirname;
use function explode;
use function implode;
use function str_replace;
use function str_starts_with;
use function substr;
use SebastianBergmann\CodeCoverage\Data\ProcessedCodeCoverageData;

/**
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for phpunit/php-code-coverage
 */
final class PathReducer
{
    /**
     * Reduces the paths by cutting the longest common start path, renames the
     * files in the coverage data accordingly, and returns the common path.
     *
     * Returns an empty string when there are no covered files.
     */
    public function reduce(ProcessedCodeCoverageData $data): string
    {
        $coveredFiles = $data->coveredFiles();

        if ($coveredFiles === []) {
            return '';
        }

        if (count($coveredFiles) === 1) {
            $file       = $coveredFiles[0];
            $normalised = $file;

            if (str_starts_with($file, 'phar://')) {
                $normalised = str_replace('/', DIRECTORY_SEPARATOR, substr($file, 7));
            }

            $data->renameFile($file, basename($normalised));

            return dirname($normalised);
        }

        $original = $coveredFiles;
        $paths    = $coveredFiles;
        $max      = count($paths);

        for ($i = 0; $i < $max; $i++) {
            if (str_starts_with($paths[$i], 'phar://')) {
                $paths[$i] = substr($paths[$i], 7);
                $paths[$i] = str_replace('/', DIRECTORY_SEPARATOR, $paths[$i]);
            }

            $paths[$i] = explode(DIRECTORY_SEPARATOR, $paths[$i]);

            if ($paths[$i][0] === '') {
                $paths[$i][0] = DIRECTORY_SEPARATOR;
            }
        }

        $commonPath = '';
        $done       = false;

        while (!$done) {
            for ($i = 0; $i < $max - 1; $i++) {
                if (!isset($paths[$i][0]) || !isset($paths[$i + 1][0]) ||
                    $paths[$i][0] !== $paths[$i + 1][0]) {
                    $done = true;

                    break;
                }
            }

            if (!$done) {
                $commonPath .= $paths[0][0];

                if ($paths[0][0] !== DIRECTORY_SEPARATOR) {
                    $commonPath .= DIRECTORY_SEPARATOR;
                }

                for ($i = 0; $i < $max; $i++) {
                    array_shift($paths[$i]);
                }
            }
        }

        for ($i = 0; $i < $max; $i++) {
            $newPath = implode(DIRECTORY_SEPARATOR, $paths[$i]);

            if ($newPath !== $original[$i]) {
                $data->renameFile($original[$i], $newPath);
            }
        }

        return substr($commonPath, 0, -1);
    }
}
