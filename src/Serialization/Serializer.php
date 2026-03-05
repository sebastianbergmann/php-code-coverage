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
use function serialize;
use DateTimeImmutable;
use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Data\ProcessedCodeCoverageData;
use SebastianBergmann\CodeCoverage\Util\Filesystem;
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

        $data = [
            'buildInformation' => $buildInformation,
            'codeCoverage'     => $codeCoverage->getData(),
            'testResults'      => $codeCoverage->getTests(),
        ];

        Filesystem::write(
            $target,
            '<?php // phpunit/php-code-coverage version ' . Version::id() . PHP_EOL .
            "return \unserialize(<<<'END_OF_COVERAGE_SERIALIZATION'" . PHP_EOL .
            serialize($data) . PHP_EOL .
            'END_OF_COVERAGE_SERIALIZATION' . PHP_EOL .
            ');',
        );
    }
}
