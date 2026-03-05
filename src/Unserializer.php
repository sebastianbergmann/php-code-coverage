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

use function fclose;
use function fgets;
use function fopen;
use function preg_match;
use function trim;
use SebastianBergmann\CodeCoverage\Driver\NullDriver;

/**
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for phpunit/php-code-coverage
 */
final readonly class Unserializer
{
    /**
     * @param non-empty-string $path
     */
    public function unserialize(string $path): CodeCoverage
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

        if (preg_match('/^<\?php \/\/ phpunit\/php-code-coverage version (.+)$/', trim($firstLine), $matches) !== 1) {
            throw new FileCouldNotBeReadException('File does not contain phpunit/php-code-coverage version information: ' . $path);
        }

        $storedVersion = $matches[1];

        if ($storedVersion !== Version::id()) {
            throw new VersionMismatchException($storedVersion, Version::id());
        }

        $data = require $path;

        $configuration = $data['configuration'];

        $codeCoverage = new CodeCoverage(new NullDriver, $configuration['filter']);

        if ($configuration['cacheDirectory'] !== null) {
            $codeCoverage->cacheStaticAnalysis($configuration['cacheDirectory']);
        }

        if ($configuration['checkForUnintentionallyCoveredCode'] === true) {
            $codeCoverage->enableCheckForUnintentionallyCoveredCode();
        }

        if ($configuration['includeUncoveredFiles'] === false) {
            $codeCoverage->excludeUncoveredFiles();
        }

        if ($configuration['ignoreDeprecatedCode'] === true) {
            $codeCoverage->ignoreDeprecatedCode();
        }

        if ($configuration['useAnnotationsForIgnoringCode'] === false) {
            $codeCoverage->disableAnnotationsForIgnoringCode();
        }

        foreach ($configuration['parentClassesExcludedFromUnintentionallyCoveredCodeCheck'] as $className) {
            $codeCoverage->excludeSubclassesOfThisClassFromUnintentionallyCoveredCodeCheck($className);
        }

        $codeCoverage->setData($data['codeCoverage']);
        $codeCoverage->setTests($data['testResults']);

        return $codeCoverage;
    }
}
