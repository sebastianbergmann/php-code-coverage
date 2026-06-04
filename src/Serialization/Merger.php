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

use function array_key_exists;
use function array_merge;
use function assert;
use DateTimeImmutable;
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

            $mergedCoverage->merge($item['codeCoverage']);

            $mergedTestResults[] = $item['testResults'];
        }

        if (!isset($first)) {
            throw new EmptyPathListException;
        }

        assert(isset($mergedCoverage));
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
            'basePath'         => $first['basePath'],
            'codeCoverage'     => $mergedCoverage,
            'testResults'      => array_merge(...$mergedTestResults),
        ];
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
