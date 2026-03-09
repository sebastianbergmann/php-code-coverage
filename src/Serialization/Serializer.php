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

use const PHP_EOL;
use function preg_replace_callback;
use function serialize;
use function str_starts_with;
use DateTimeImmutable;
use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Data\ProcessedCodeCoverageData;
use SebastianBergmann\CodeCoverage\Util\Filesystem;
use SebastianBergmann\CodeCoverage\Util\PathReducer;
use SebastianBergmann\CodeCoverage\Version;
use SebastianBergmann\Environment\Runtime;
use SebastianBergmann\GitState\Builder as GitStateBuilder;

/**
 * @phpstan-type SerializedCoverage array{
 *     buildInformation: array{
 *         timestamp: string,
 *         runtime: array{
 *             name: string,
 *             version: string,
 *             vendorUrl: string,
 *         },
 *         phpCodeCoverage: array{
 *             version: string,
 *             driverInformation: array{
 *                 name: non-empty-string,
 *                 version: non-empty-string,
 *             },
 *         },
 *         git?: array{
 *             originUrl: string,
 *             branch: string,
 *             commit: string,
 *             isClean: bool,
 *             status: string,
 *         },
 *     },
 *     basePath: string,
 *     codeCoverage: ProcessedCodeCoverageData,
 *     testResults: array<string, array{size: string, status: string, time: float}>,
 * }
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for phpunit/php-code-coverage
 */
final readonly class Serializer
{
    /**
     * @param non-empty-string $target
     *
     * @throws PharPrefixCouldNotBeStrippedException
     */
    public function serialize(string $target, CodeCoverage $codeCoverage, bool $includeGitInformation = false): void
    {
        $runtime = new Runtime;

        $buildInformation = [
            'timestamp' => (new DateTimeImmutable)->format('D M j G:i:s T Y'),
            'runtime'   => [
                'name'      => $runtime->getName(),
                'version'   => $runtime->getVersion(),
                'vendorUrl' => $runtime->getVendorUrl(),
            ],
            'phpCodeCoverage' => [
                'version'           => Version::id(),
                'driverInformation' => $codeCoverage->driverInformation(),
            ],
        ];

        if ($includeGitInformation) {
            $gitInformation = (new GitStateBuilder)->build();

            if ($gitInformation !== false) {
                $buildInformation['git'] = [
                    'originUrl' => $gitInformation->originUrl(),
                    'branch'    => $gitInformation->branch(),
                    'commit'    => $gitInformation->commit(),
                    'isClean'   => $gitInformation->isClean(),
                    'status'    => $gitInformation->status(),
                ];
            }
        }

        $coverageData = clone $codeCoverage->getData();
        $basePath     = (new PathReducer)->reduce($coverageData);

        $data = [
            'buildInformation' => $buildInformation,
            'basePath'         => $basePath,
            'codeCoverage'     => $coverageData,
            'testResults'      => $codeCoverage->getTests(),
        ];

        $serializedData = serialize($data);

        if (str_starts_with(self::class, 'PHPUnitPHAR\\')) {
            // @codeCoverageIgnoreStart
            $serializedData = $this->stripPharPrefix($serializedData);
            // @codeCoverageIgnoreEnd
        }

        Filesystem::write(
            $target,
            '<?php // phpunit/php-code-coverage version ' . Version::id() . PHP_EOL .
            "return \unserialize(<<<'END_OF_COVERAGE_SERIALIZATION'" . PHP_EOL .
            $serializedData . PHP_EOL .
            'END_OF_COVERAGE_SERIALIZATION' . PHP_EOL .
            ');',
        );
    }

    /**
     * @param non-empty-string $serialized
     *
     * @throws PharPrefixCouldNotBeStrippedException
     *
     * @return non-empty-string
     */
    private function stripPharPrefix(string $serialized): string
    {
        $result = preg_replace_callback(
            '/([OCs]):(\d+):"(\x00?)PHPUnitPHAR\\\\/',
            static function (array $matches): string
            {
                return $matches[1] . ':' . ((int) $matches[2] - 12) . ':"' . $matches[3];
            },
            $serialized,
        );

        if ($result === null) {
            // @codeCoverageIgnoreStart
            throw new PharPrefixCouldNotBeStrippedException;
            // @codeCoverageIgnoreEnd
        }

        return $result;
    }
}
