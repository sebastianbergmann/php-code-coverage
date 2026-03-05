<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage;

use const PHP_EOL;
use function serialize;
use SebastianBergmann\CodeCoverage\Util\Filesystem;

/**
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 */
final readonly class Serializer
{
    /**
     * @param non-empty-string $target
     */
    public function serialize(string $target, CodeCoverage $codeCoverage): void
    {
        $data = [
            'configuration' => $codeCoverage->configuration(),
            'codeCoverage'  => $codeCoverage->getData(),
            'testResults'   => $codeCoverage->getTests(),
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
