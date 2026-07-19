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

use function class_exists;
use function explode;
use function file_get_contents;
use function filemtime;
use function filesize;
use function is_file;
use function str_starts_with;
use function strlen;
use function substr;
use function trim;
use Composer\InstalledVersions;

/**
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for phpunit/php-code-coverage
 */
final class PhpParserVersion
{
    private const string PACKAGE = 'nikic/php-parser';

    /**
     * @var ?non-empty-string
     */
    private static ?string $version = null;

    /**
     * Returns a string that identifies the version of nikic/php-parser that is
     * used: the version (and source reference) reported by Composer, or the
     * version recorded in the manifest of the PHP Archive this library is
     * bundled in. When the manifest is not available, an identifier is derived
     * from the PHP Archive file itself, which changes whenever the bundled
     * version of nikic/php-parser can change. The constant string 'unknown' is
     * the last resort.
     *
     * @return non-empty-string
     */
    public static function id(): string
    {
        if (self::$version === null) {
            self::$version = self::detect();
        }

        return self::$version;
    }

    /**
     * @return ?non-empty-string
     */
    public static function versionFromManifest(string $manifest): ?string
    {
        foreach (explode("\n", $manifest) as $line) {
            if (!str_starts_with($line, self::PACKAGE . ': ')) {
                continue;
            }

            $version = trim(substr($line, strlen(self::PACKAGE . ': ')));

            if ($version !== '') {
                return $version;
            }
        }

        return null;
    }

    /**
     * @return non-empty-string
     */
    private static function detect(): string
    {
        if (Phar::isBundled()) {
            // @codeCoverageIgnoreStart
            return self::versionFromPharManifest();
            // @codeCoverageIgnoreEnd
        }

        return self::versionFromComposer();
    }

    /**
     * @return non-empty-string
     */
    private static function versionFromComposer(): string
    {
        if (!class_exists(InstalledVersions::class) || !InstalledVersions::isInstalled(self::PACKAGE)) {
            // @codeCoverageIgnoreStart
            return 'unknown';
            // @codeCoverageIgnoreEnd
        }

        $version   = InstalledVersions::getPrettyVersion(self::PACKAGE);
        $reference = InstalledVersions::getReference(self::PACKAGE);

        if ($version === null || $version === '') {
            // @codeCoverageIgnoreStart
            return 'unknown';
            // @codeCoverageIgnoreEnd
        }

        if ($reference === null) {
            return $version;
        }

        return $version . ' (' . $reference . ')';
    }

    /**
     * @codeCoverageIgnore
     *
     * @return non-empty-string
     */
    private static function versionFromPharManifest(): string
    {
        $phar = \Phar::running(false);

        if ($phar === '') {
            return 'unknown';
        }

        $manifest = 'phar://' . $phar . '/manifest.txt';

        if (is_file($manifest)) {
            $contents = file_get_contents($manifest);

            if ($contents !== false) {
                $version = self::versionFromManifest($contents);

                if ($version !== null) {
                    return $version;
                }
            }
        }

        $modificationTime = filemtime($phar);
        $size             = filesize($phar);

        if ($modificationTime !== false && $size !== false) {
            return $phar . ':' . $modificationTime . ':' . $size;
        }

        return 'unknown';
    }
}
