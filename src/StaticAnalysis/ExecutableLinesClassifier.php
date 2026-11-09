<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\StaticAnalysis;

use const PHP_VERSION;
use function function_exists;
use function ini_get;
use function intval;
use function is_array;
use function opcache_get_status;

/**
 * How the executable lines of a file are determined.
 *
 * The analysis of the syntax of a file approximates which lines PHP attributes
 * code to when it compiles the file. The bytecode of a file states this for the
 * way the file is compiled for that analysis (see {@see BytecodeLines}).
 *
 * The lines that a driver reports for a line that was executed need to match
 * the lines that are counted as executable. Drivers that observe the bytecode
 * PHP executes report the lines that the opcodes of that bytecode are
 * attributed to (see Driver::linesDependOnCompilation()). For them, the
 * executable lines are only determined from the bytecode when PHP compiles the
 * code the same way as the bytecode analysis does: by OPcache, without
 * optimizations, and without exceptions for files that were modified recently.
 * PHP's compiler substitutes constants of classes that are already loaded
 * when OPcache does not compile a file, for instance, which changes the
 * bytecode of a match expression.
 *
 * @internal This enumeration is not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for phpunit/php-code-coverage
 */
enum ExecutableLinesClassifier: string
{
    public static function select(bool $linesDependOnCompilation): self
    {
        if ($linesDependOnCompilation && !self::compilationMatchesBytecodeAnalysis()) {
            return self::Syntax;
        }

        if (!BytecodeLines::isAvailable()) {
            return self::Syntax;
        }

        return self::Bytecode;
    }

    /**
     * Whether code is compiled the same way in this process as it is compiled
     * for the bytecode analysis.
     */
    public static function compilationMatchesBytecodeAnalysis(): bool
    {
        $status = function_exists('opcache_get_status') ? @opcache_get_status(false) : false;

        return self::isCompiledLikeBytecodeAnalysis(
            is_array($status) && ($status['opcache_enabled'] ?? false) === true,
            ini_get('opcache.optimization_level'),
            ini_get('opcache.file_update_protection'),
        );
    }

    /**
     * @param false|string $optimizationLevel    the value of the opcache.optimization_level setting
     * @param false|string $fileUpdateProtection the value of the opcache.file_update_protection setting
     */
    public static function isCompiledLikeBytecodeAnalysis(bool $opcacheIsEnabled, false|string $optimizationLevel, false|string $fileUpdateProtection): bool
    {
        return $opcacheIsEnabled &&
               $optimizationLevel !== false &&
               intval($optimizationLevel, 0) === 0 &&
               $fileUpdateProtection !== false &&
               intval($fileUpdateProtection, 0) === 0;
    }

    /**
     * The component of the key under which the results of the analysis of a
     * file are cached that depends on how its executable lines are determined.
     */
    public function cacheKey(): string
    {
        if ($this === self::Syntax) {
            return '';
        }

        return 'bytecode:' . PHP_VERSION;
    }
    case Syntax   = 'syntax';
    case Bytecode = 'bytecode';
}
