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
use function array_diff;
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
     * Returns an empty string when there are no covered files, or when they
     * have no common path. Returns the directory separator when the root
     * directory is all that they have in common.
     *
     * The additional files are not part of the coverage data, but the common
     * path is the one that the covered files share with them. When they are
     * the files that could have been covered then the common path, and with
     * it the paths the covered files are renamed to, do not depend on which
     * of these files actually were covered. The additional files are ignored
     * when the covered files have no common path with them.
     *
     * @param list<non-empty-string> $additionalFiles
     */
    public function reduce(ProcessedCodeCoverageData $data, array $additionalFiles = []): string
    {
        $coveredFiles = $data->coveredFiles();

        if ($coveredFiles === []) {
            return '';
        }

        $additionalFiles = array_diff($additionalFiles, $coveredFiles);

        if (count($coveredFiles) === 1 && $additionalFiles === []) {
            $file       = $coveredFiles[0];
            $normalised = $file;

            if (str_starts_with($file, 'phar://')) {
                $normalised = str_replace('/', DIRECTORY_SEPARATOR, substr($file, 7));
            }

            $newFile = basename($normalised);

            if ($newFile !== '' && $newFile !== $file) {
                $data->renameFile($file, $newFile);
            }

            return dirname($normalised);
        }

        $paths = [];

        foreach ($coveredFiles as $coveredFile) {
            $paths[] = [
                'original' => $coveredFile,
                'covered'  => true,
                'parts'    => $this->parts($coveredFile),
            ];
        }

        foreach ($additionalFiles as $additionalFile) {
            $paths[] = [
                'original' => $additionalFile,
                'covered'  => false,
                'parts'    => $this->parts($additionalFile),
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

        // The additional files must not be the reason why the covered files,
        // which have a common path, are not renamed at all
        if ($additionalFiles !== [] && $commonPath === '') {
            return $this->reduce($data);
        }

        foreach ($paths as $path) {
            $newPath = implode(DIRECTORY_SEPARATOR, $path['parts']);

            if ($path['covered'] && $newPath !== '' && $newPath !== $path['original']) {
                $data->renameFile($path['original'], $newPath);
            }
        }

        if ($commonPath === DIRECTORY_SEPARATOR) {
            return $commonPath;
        }

        return substr($commonPath, 0, -1);
    }

    /**
     * @param non-empty-string $file
     *
     * @return non-empty-list<string>
     */
    private function parts(string $file): array
    {
        if (str_starts_with($file, 'phar://')) {
            $file = str_replace('/', DIRECTORY_SEPARATOR, substr($file, 7));
        }

        $parts = explode(DIRECTORY_SEPARATOR, $file);

        if ($parts[0] === '') {
            $parts[0] = DIRECTORY_SEPARATOR;
        }

        return $parts;
    }
}
