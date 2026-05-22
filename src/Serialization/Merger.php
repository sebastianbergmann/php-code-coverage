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
        $first             = null;
        $refRuntime        = null;
        $refDriver         = null;
        $refHasGit         = null;
        $refGit            = null;
        $mergedTestResults = null;
        $runtime           = null;

        foreach ($paths as $path) {
            $unserializer ??= new Unserializer;

            $first ??= $unserializer->unserialize($path);
            $refRuntime ??= $first['buildInformation']['runtime'];
            $refDriver ??= $first['buildInformation']['phpCodeCoverage']['driverInformation'];
            $refHasGit ??= array_key_exists('git', $first['buildInformation']);
            $refGit ??= $first['buildInformation']['git'] ?? null;

            $mergedCoverage ??= clone $first['codeCoverage'];
            $mergedTestResults ??= [$first['testResults']];

            $item    = $unserializer->unserialize($path);
            $runtime = $item['buildInformation']['runtime'];

            if ($requireMatchingPhpVersion) {
                if ($runtime['name'] !== $refRuntime['name'] || $runtime['version'] !== $refRuntime['version']) {
                    throw new RuntimeMismatchException;
                }
            }

            if ($requireMatchingCodeCoverageDriver) {
                $driver = $item['buildInformation']['phpCodeCoverage']['driverInformation'];

                if ($driver['name'] !== $refDriver['name'] || $driver['version'] !== $refDriver['version']) {
                    throw new DriverMismatchException;
                }
            }

            if ($requireMatchingGitInformation) {
                $hasGit = array_key_exists('git', $item['buildInformation']);

                if ($hasGit !== $refHasGit) {
                    throw new MixedGitInformationException;
                }

                if ($hasGit) {
                    $git = $item['buildInformation']['git'];

                    foreach (['originUrl', 'branch', 'commit', 'status'] as $field) {
                        if ($git[$field] !== $refGit[$field]) {
                            throw new GitInformationMismatchException($field, (string) $refGit[$field], (string) $git[$field]);
                        }
                    }

                    if ($git['isClean'] !== $refGit['isClean']) {
                        throw new GitInformationMismatchException(
                            'isClean',
                            $refGit['isClean'] ? 'true' : 'false',
                            $git['isClean'] ? 'true' : 'false',
                        );
                    }
                }
            }

            $mergedCoverage->merge($item['codeCoverage']);

            $mergedTestResults[] = $item['testResults'];
        }

        if (!isset($first)) {
            throw new EmptyPathListException;
        }

        $buildInformation = [
            'timestamp'       => (new DateTimeImmutable)->format('D M j G:i:s T Y'),
            'runtime'         => $refRuntime,
            'phpCodeCoverage' => [
                'version'             => Version::id(),
                'serializationFormat' => Serializer::SERIALIZATION_FORMAT,
                'driverInformation'   => $refDriver,
            ],
        ];

        if ($refHasGit) {
            $buildInformation['git'] = $refGit;
        }

        return [
            'buildInformation' => $buildInformation,
            'basePath'         => $first['basePath'],
            'codeCoverage'     => $mergedCoverage,
            'testResults'      => array_merge(...$mergedTestResults),
        ];
    }
}
