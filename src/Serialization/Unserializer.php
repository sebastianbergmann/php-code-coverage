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
use function fclose;
use function fgets;
use function fopen;
use function is_array;
use function is_bool;
use function is_int;
use function is_string;
use function preg_match;
use function restore_error_handler;
use function set_error_handler;
use function trim;
use SebastianBergmann\CodeCoverage\Data\ProcessedCodeCoverageData;

/**
 * @phpstan-import-type SerializedCoverage from Serializer
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for phpunit/php-code-coverage
 */
final readonly class Unserializer
{
    /**
     * @param non-empty-string $path
     *
     * @throws FileCouldNotBeReadException
     * @throws InvalidCoverageDataException
     * @throws VersionMismatchException
     *
     * @return SerializedCoverage
     */
    public function unserialize(string $path): array
    {
        $file = @fopen($path, 'r');

        if ($file === false) {
            throw new FileCouldNotBeReadException('Cannot open file: ' . $path);
        }

        $firstLine = fgets($file);

        fclose($file);

        if ($firstLine === false) {
            throw new FileCouldNotBeReadException('Cannot read from file: ' . $path);
        }

        if (preg_match('/^<\?php \/\/ phpunit\/php-code-coverage serialization format (\d+)$/', trim($firstLine), $matches) !== 1) {
            throw new FileCouldNotBeReadException('File does not contain phpunit/php-code-coverage serialization format information: ' . $path);
        }

        $storedFormat = (int) $matches[1];

        if ($storedFormat !== Serializer::SERIALIZATION_FORMAT) {
            throw new VersionMismatchException($storedFormat, Serializer::SERIALIZATION_FORMAT);
        }

        set_error_handler(
            static function (int $errno, string $errstr) use ($path): never
            {
                throw new InvalidCoverageDataException(
                    'Failed to unserialize coverage data from ' . $path . ': ' . $errstr,
                );
            },
        );

        try {
            $data = require $path;
        } finally {
            restore_error_handler();
        }

        $this->validate($data);

        return $data;
    }

    /**
     * @throws InvalidCoverageDataException
     */
    private function validate(mixed $data): void
    {
        if (!is_array($data)) {
            throw new InvalidCoverageDataException('Coverage data is not an array');
        }

        if (!array_key_exists('buildInformation', $data) || !is_array($data['buildInformation'])) {
            throw new InvalidCoverageDataException('Coverage data is missing valid \'buildInformation\' key');
        }

        $buildInformation = $data['buildInformation'];

        if (!array_key_exists('timestamp', $buildInformation) || !is_string($buildInformation['timestamp'])) {
            throw new InvalidCoverageDataException('Coverage data is missing valid \'buildInformation.timestamp\' key');
        }

        if (!array_key_exists('runtime', $buildInformation) || !is_array($buildInformation['runtime'])) {
            throw new InvalidCoverageDataException('Coverage data is missing valid \'buildInformation.runtime\' key');
        }

        $runtime = $buildInformation['runtime'];

        foreach (['name', 'version', 'vendorUrl'] as $key) {
            if (!array_key_exists($key, $runtime) || !is_string($runtime[$key])) {
                throw new InvalidCoverageDataException('Coverage data is missing valid \'buildInformation.runtime.' . $key . '\' key');
            }
        }

        if (!array_key_exists('phpCodeCoverage', $buildInformation) || !is_array($buildInformation['phpCodeCoverage'])) {
            throw new InvalidCoverageDataException('Coverage data is missing valid \'buildInformation.phpCodeCoverage\' key');
        }

        $phpCodeCoverage = $buildInformation['phpCodeCoverage'];

        if (!array_key_exists('version', $phpCodeCoverage) || !is_string($phpCodeCoverage['version'])) {
            throw new InvalidCoverageDataException('Coverage data is missing valid \'buildInformation.phpCodeCoverage.version\' key');
        }

        if (!array_key_exists('serializationFormat', $phpCodeCoverage) || !is_int($phpCodeCoverage['serializationFormat'])) {
            throw new InvalidCoverageDataException('Coverage data is missing valid \'buildInformation.phpCodeCoverage.serializationFormat\' key');
        }

        if (!array_key_exists('driverInformation', $phpCodeCoverage) || !is_array($phpCodeCoverage['driverInformation'])) {
            throw new InvalidCoverageDataException('Coverage data is missing valid \'buildInformation.phpCodeCoverage.driverInformation\' key');
        }

        $driverInformation = $phpCodeCoverage['driverInformation'];

        foreach (['name', 'version'] as $key) {
            if (!array_key_exists($key, $driverInformation) || !is_string($driverInformation[$key]) || $driverInformation[$key] === '') {
                throw new InvalidCoverageDataException('Coverage data is missing valid \'buildInformation.phpCodeCoverage.driverInformation.' . $key . '\' key');
            }
        }

        if (array_key_exists('git', $buildInformation)) {
            if (!is_array($buildInformation['git'])) {
                throw new InvalidCoverageDataException('Coverage data has invalid \'buildInformation.git\' key');
            }

            $git = $buildInformation['git'];

            foreach (['originUrl', 'branch', 'commit', 'status'] as $key) {
                if (!array_key_exists($key, $git) || !is_string($git[$key])) {
                    throw new InvalidCoverageDataException('Coverage data is missing valid \'buildInformation.git.' . $key . '\' key');
                }
            }

            if (!array_key_exists('isClean', $git) || !is_bool($git['isClean'])) {
                throw new InvalidCoverageDataException('Coverage data is missing valid \'buildInformation.git.isClean\' key');
            }
        }

        if (!array_key_exists('basePath', $data) || !is_string($data['basePath'])) {
            throw new InvalidCoverageDataException('Coverage data is missing valid \'basePath\' key');
        }

        if (!array_key_exists('codeCoverage', $data) || !$data['codeCoverage'] instanceof ProcessedCodeCoverageData) {
            throw new InvalidCoverageDataException('Coverage data is missing valid \'codeCoverage\' key');
        }

        if (!array_key_exists('testResults', $data) || !is_array($data['testResults'])) {
            throw new InvalidCoverageDataException('Coverage data is missing valid \'testResults\' key');
        }
    }
}
