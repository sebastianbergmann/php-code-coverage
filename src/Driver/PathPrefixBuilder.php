<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\Driver;

use const DIRECTORY_SEPARATOR;
use function array_flip;
use function dirname;
use function ksort;
use function rtrim;
use function str_starts_with;

/**
 * Builds the path prefixes a code coverage driver is limited to from the files
 * it is to collect code coverage data for.
 *
 * Matching the code that is compiled against a few directories is considerably
 * cheaper than matching it against many files. A directory also matches files
 * in it that code coverage data is not to be collected for, though, and data
 * that is collected for such a file is collected only to be removed when the
 * code coverage data is processed.
 *
 * A directory is therefore only used when files that code coverage data is to
 * be collected for are in it, not just in one of its subdirectories, and when
 * none of the files that must not be matched is in it or in one of its
 * subdirectories. The files that are in a directory that is not used are used
 * as they are.
 *
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for phpunit/php-code-coverage
 */
final readonly class PathPrefixBuilder
{
    /**
     * @param list<non-empty-string> $files                     the files to collect code coverage data for
     * @param list<string>           $filesThatMustNotBeMatched files that were already loaded, for instance
     *
     * @return list<non-empty-string>
     */
    public function build(array $files, array $filesThatMustNotBeMatched): array
    {
        $filesByDirectory = [];

        foreach ($files as $file) {
            $filesByDirectory[$this->directory($file)][] = $file;
        }

        ksort($filesByDirectory);

        $directoriesThatMustNotBeUsed = $this->directoriesThatMustNotBeUsed($files, $filesThatMustNotBeMatched);
        $pathPrefixes                 = [];
        $directoryUsedMostRecently    = null;

        foreach ($filesByDirectory as $directory => $filesInDirectory) {
            // The directories are sorted: the subdirectories of a directory
            // follow it immediately
            if ($directoryUsedMostRecently !== null && str_starts_with($directory, $directoryUsedMostRecently)) {
                continue;
            }

            if (isset($directoriesThatMustNotBeUsed[$directory])) {
                foreach ($filesInDirectory as $file) {
                    $pathPrefixes[] = $file;
                }

                continue;
            }

            $pathPrefixes[]            = $directory;
            $directoryUsedMostRecently = $directory;
        }

        return $pathPrefixes;
    }

    /**
     * @param list<non-empty-string> $files
     * @param list<string>           $filesThatMustNotBeMatched
     *
     * @return array<non-empty-string, true>
     */
    private function directoriesThatMustNotBeUsed(array $files, array $filesThatMustNotBeMatched): array
    {
        $files       = array_flip($files);
        $directories = [];

        foreach ($filesThatMustNotBeMatched as $file) {
            if ($file === '' || isset($files[$file])) {
                continue;
            }

            $directory = $this->directory($file);

            while (!isset($directories[$directory])) {
                $directories[$directory] = true;

                $directory = $this->directory($directory);
            }
        }

        return $directories;
    }

    /**
     * Returns the directory, with a trailing directory separator, that a file
     * or a directory is in.
     *
     * @param non-empty-string $path
     *
     * @return non-empty-string
     */
    private function directory(string $path): string
    {
        return rtrim(dirname($path), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }
}
