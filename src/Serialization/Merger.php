<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\Serialization;

use const DIRECTORY_SEPARATOR;
use function array_key_exists;
use function array_merge;
use function assert;
use function str_ends_with;
use function str_starts_with;
use function strlen;
use function substr;
use function usort;
use DateTimeImmutable;
use SebastianBergmann\CodeCoverage\Data\ProcessedCodeCoverageData;
use SebastianBergmann\CodeCoverage\Version;

/**
 * @phpstan-import-type SerializedCoverage from Serializer
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for phpunit/php-code-coverage
 */
final readonly class Merger
{
    /**
     * @param iterable<non-empty-string> $paths
     *
     * @throws DriverMismatchException
     * @throws EmptyPathListException
     * @throws GitInformationMismatchException
     * @throws MixedGitInformationException
     * @throws RuntimeMismatchException
     *
     * @return SerializedCoverage
     */
    public function merge(iterable $paths, bool $requireMatchingGitInformation = true, bool $requireMatchingPhpVersion = true, bool $requireMatchingCodeCoverageDriver = true): array
    {
        $unserializer = new Unserializer;

        foreach ($paths as $path) {
            if (!isset($first, $mergedCoverage, $mergedTestResults)) {
                $first = $unserializer->unserialize($path);

                $mergedCoverage    = clone $first['codeCoverage'];
                $mergedBasePath    = $first['basePath'];
                $mergedTestResults = [$first['testResults']];

                continue;
            }

            $item = $unserializer->unserialize($path);

            if ($requireMatchingPhpVersion) {
                $this->assertPhpVersionMatches($first, $item);
            }

            if ($requireMatchingCodeCoverageDriver) {
                $this->assertDriverMatches($first, $item);
            }

            if ($requireMatchingGitInformation) {
                $this->assertGitInformationMatches($first, $item);
            }

            assert(isset($mergedBasePath));

            $mergedBasePath = $this->alignPaths($mergedCoverage, $mergedBasePath, $item['codeCoverage'], $item['basePath']);

            $mergedCoverage->merge($item['codeCoverage']);

            $mergedTestResults[] = $item['testResults'];
        }

        if (!isset($first)) {
            throw new EmptyPathListException;
        }

        assert(isset($mergedCoverage));
        assert(isset($mergedBasePath));
        assert(isset($mergedTestResults));

        $buildInformation = [
            'timestamp'       => (new DateTimeImmutable)->format('D M j G:i:s T Y'),
            'runtime'         => $first['buildInformation']['runtime'],
            'phpCodeCoverage' => [
                'version'             => Version::id(),
                'serializationFormat' => Serializer::SERIALIZATION_FORMAT,
                'driverInformation'   => $first['buildInformation']['phpCodeCoverage']['driverInformation'],
            ],
        ];

        if (array_key_exists('git', $first['buildInformation'])) {
            $buildInformation['git'] = $first['buildInformation']['git'];
        }

        return [
            'buildInformation' => $buildInformation,
            'basePath'         => $mergedBasePath,
            'codeCoverage'     => $mergedCoverage,
            'testResults'      => array_merge(...$mergedTestResults),
        ];
    }

    /**
     * The paths in serialized code coverage data are relative to the base path of that data:
     * data that has a different base path must not be merged by these paths as they are.
     *
     * When one base path is a directory below the other one then the paths of the data that
     * has the base path further down are moved onto the base path further up.
     *
     * When neither base path is below the other one then the paths are left alone, and the
     * base path of the data that was merged so far is kept: this is what code coverage data
     * that was collected for the same files in different locations, on different machines
     * for instance, looks like.
     *
     * @return string the base path of the merged data
     */
    private function alignPaths(ProcessedCodeCoverageData $merged, string $mergedBasePath, ProcessedCodeCoverageData $current, string $currentBasePath): string
    {
        if ($mergedBasePath === $currentBasePath || $current->coveredFiles() === []) {
            return $mergedBasePath;
        }

        if ($merged->coveredFiles() === []) {
            return $currentBasePath;
        }

        $prefix = $this->relativePath($mergedBasePath, $currentBasePath);

        if ($prefix !== null) {
            $this->prefixFiles($current, $prefix);

            return $mergedBasePath;
        }

        $prefix = $this->relativePath($currentBasePath, $mergedBasePath);

        if ($prefix !== null) {
            $this->prefixFiles($merged, $prefix);

            return $currentBasePath;
        }

        return $mergedBasePath;
    }

    /**
     * @return ?non-empty-string the path relative to the directory, null when the path is not below the directory
     */
    private function relativePath(string $directory, string $path): ?string
    {
        // The path of the root directory already ends with the directory separator
        if (!str_ends_with($directory, DIRECTORY_SEPARATOR)) {
            $directory .= DIRECTORY_SEPARATOR;
        }

        if (!str_starts_with($path, $directory)) {
            return null;
        }

        $relativePath = substr($path, strlen($directory));

        if ($relativePath === '') {
            return null;
        }

        return $relativePath;
    }

    /**
     * @param non-empty-string $prefix
     */
    private function prefixFiles(ProcessedCodeCoverageData $data, string $prefix): void
    {
        $files = $data->coveredFiles();

        // Longest path first: a file must not be renamed to the path of
        // a file that has not been renamed yet
        usort($files, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));

        foreach ($files as $file) {
            $data->renameFile($file, $prefix . DIRECTORY_SEPARATOR . $file);
        }
    }

    /**
     * @param SerializedCoverage $reference
     * @param SerializedCoverage $current
     *
     * @throws RuntimeMismatchException
     */
    private function assertPhpVersionMatches(array $reference, array $current): void
    {
        $referenceRuntime = $reference['buildInformation']['runtime'];
        $currentRuntime   = $current['buildInformation']['runtime'];

        if (
            $referenceRuntime['name'] !== $currentRuntime['name'] ||
            $referenceRuntime['version'] !== $currentRuntime['version']
        ) {
            throw new RuntimeMismatchException;
        }
    }

    /**
     * @param SerializedCoverage $reference
     * @param SerializedCoverage $current
     *
     * @throws DriverMismatchException
     */
    private function assertDriverMatches(array $reference, array $current): void
    {
        $referenceDriver = $reference['buildInformation']['phpCodeCoverage']['driverInformation'];
        $currentDriver   = $current['buildInformation']['phpCodeCoverage']['driverInformation'];

        if (
            $referenceDriver['name'] !== $currentDriver['name'] ||
            $referenceDriver['version'] !== $currentDriver['version']
        ) {
            throw new DriverMismatchException;
        }
    }

    /**
     * @param SerializedCoverage $reference
     * @param SerializedCoverage $current
     *
     * @throws GitInformationMismatchException
     * @throws MixedGitInformationException
     */
    private function assertGitInformationMatches(array $reference, array $current): void
    {
        $refHasGit = array_key_exists('git', $reference['buildInformation']);

        if ($refHasGit !== array_key_exists('git', $current['buildInformation'])) {
            throw new MixedGitInformationException;
        }

        if (array_key_exists('git', $reference['buildInformation']) &&
            array_key_exists('git', $current['buildInformation'])) {
            $referenceGit = $reference['buildInformation']['git'];
            $currentGit   = $current['buildInformation']['git'];

            foreach (['originUrl', 'branch', 'commit', 'status'] as $field) {
                if ($currentGit[$field] !== $referenceGit[$field]) {
                    throw new GitInformationMismatchException($field, (string) $referenceGit[$field], (string) $currentGit[$field]);
                }
            }

            if ($currentGit['isClean'] !== $referenceGit['isClean']) {
                throw new GitInformationMismatchException(
                    'isClean',
                    $referenceGit['isClean'] ? 'true' : 'false',
                    $currentGit['isClean'] ? 'true' : 'false',
                );
            }
        }
    }
}
