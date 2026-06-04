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
use function array_keys;
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

            $newFile = basename($normalised);

            $data->renameFile($file, $newFile === '' ? $file : $newFile);

            return dirname($normalised);
        }

        $paths = [];

        foreach ($coveredFiles as $coveredFile) {
            $normalised = $coveredFile;

            if (str_starts_with($coveredFile, 'phar://')) {
                $normalised = str_replace('/', DIRECTORY_SEPARATOR, substr($coveredFile, 7));
            }

            $parts = explode(DIRECTORY_SEPARATOR, $normalised);

            if ($parts[0] === '') {
                $parts[0] = DIRECTORY_SEPARATOR;
            }

            $paths[] = [
                'original' => $coveredFile,
                'parts'    => $parts,
            ];
        }

        $commonPath = '';

        while (true) {
            $firstSegment = $paths[0]['parts'][0] ?? null;

            if ($firstSegment === null) {
                break;
            }

            $allShareFirstSegment = true;

            foreach ($paths as $path) {
                if (($path['parts'][0] ?? null) !== $firstSegment) {
                    $allShareFirstSegment = false;

                    break;
                }
            }

            if (!$allShareFirstSegment) {
                break;
            }

            $commonPath .= $firstSegment === DIRECTORY_SEPARATOR ? $firstSegment : $firstSegment . DIRECTORY_SEPARATOR;

            foreach (array_keys($paths) as $i) {
                array_shift($paths[$i]['parts']);
            }
        }

        foreach ($paths as $path) {
            $newPath = implode(DIRECTORY_SEPARATOR, $path['parts']);

            if ($newPath !== '' && $newPath !== $path['original']) {
                $data->renameFile($path['original'], $newPath);
            }
        }

        return substr($commonPath, 0, -1);
    }
}
