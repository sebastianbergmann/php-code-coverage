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

use const FILTER_VALIDATE_BOOLEAN;
use const PHP_BINARY;
use const PHP_OS_FAMILY;
use const PHP_VERSION_ID;
use function array_key_exists;
use function array_keys;
use function array_shift;
use function fclose;
use function fgets;
use function file_put_contents;
use function filter_var;
use function function_exists;
use function implode;
use function ini_get;
use function is_resource;
use function proc_close;
use function proc_open;
use function realpath;
use function rtrim;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;
use Generator;

/**
 * Determines the executable lines of files from their bytecode.
 *
 * The files are compiled, but not executed, by OPcache in a separate PHP
 * process, which dumps their bytecode before it is optimized, with line
 * numbers; {@see BytecodeDumpParser} determines the executable lines from that
 * dump. The process is started without a configuration file (php -n), so that
 * the bytecode does not depend on the configuration of the process that
 * collects code coverage. OPcache compiles each file on its own, without
 * substituting constants of other files, so the bytecode does not depend on
 * which other files were compiled before, either.
 *
 * Starting a process takes much longer than compiling a file, so the files
 * that are about to be analysed (see {@see expect()}) are compiled in one
 * process together with the first of them that is analysed.
 *
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for phpunit/php-code-coverage
 */
final class BytecodeLines
{
    /**
     * ZEND_DUMP_BEFORE_OPTIMIZER | ZEND_DUMP_LINE_NUMBERS_PASSTHRU.
     */
    private const string DEBUG_LEVEL = '0x40010000';

    /**
     * Code that compiles the files listed in the file passed as argument.
     */
    private const string COMPILE    = 'foreach (file($argv[1], FILE_IGNORE_NEW_LINES) ?: [] as $file) { opcache_compile_file($file); }';
    private static ?bool $available = null;

    /**
     * @var non-empty-string
     */
    private readonly string $phpBinary;

    /**
     * The executable lines of each file that was compiled; null for a file
     * that could not be compiled.
     *
     * @var array<string, ?array<positive-int, true>>
     */
    private array $lines = [];

    /**
     * The files that are about to be analysed.
     *
     * @var iterable<non-empty-string>
     */
    private iterable $expected = [];

    /**
     * Whether the executable lines of files can be determined from their
     * bytecode: OPcache can dump bytecode with line numbers as of PHP 8.6, and
     * a PHP process has to be started for that.
     */
    public static function isAvailable(): bool
    {
        if (self::$available === null) {
            self::$available = PHP_VERSION_ID >= 80600 &&
                               function_exists('proc_open') &&
                               (new self)->executableLines(__FILE__) !== null;
        }

        return self::$available;
    }

    /**
     * @param ?non-empty-string $phpBinary the PHP binary that compiles the files, PHP_BINARY when null
     */
    public function __construct(?string $phpBinary = null)
    {
        $this->phpBinary = $phpBinary ?? PHP_BINARY;
    }

    /**
     * Declares the files that are about to be analysed. They replace the files
     * that were declared before, which have been analysed by then: only files
     * that are analysed are compiled, so that the result of their analysis can
     * be cached.
     *
     * The files are iterated when the next file is compiled, so that they can
     * be filtered lazily: the files whose analysis is cached, for instance,
     * need not be compiled.
     *
     * @param iterable<non-empty-string> $files
     */
    public function expect(iterable $files): void
    {
        $this->expected = $files;
    }

    /**
     * @param non-empty-string $file
     *
     * @return ?array<positive-int, true> null when the file could not be compiled
     */
    public function executableLines(string $file): ?array
    {
        if (!array_key_exists($file, $this->lines)) {
            $this->compile($file);
        }

        return $this->lines[$file] ?? null;
    }

    /**
     * Compiles the given file together with the files that are about to be
     * analysed and that have not been compiled yet. A file that cannot be compiled ends the process that
     * compiles it, so the files after it are compiled in another process.
     *
     * @param non-empty-string $file
     */
    private function compile(string $file): void
    {
        $batch = [$file => true];

        foreach ($this->expected as $candidate) {
            if (!array_key_exists($candidate, $this->lines)) {
                $batch[$candidate] = true;
            }
        }

        $this->expected = [];
        $files          = array_keys($batch);

        while ($files !== []) {
            $dumped = $this->dump($files);

            if ($dumped === null) {
                // This version of PHP cannot dump bytecode with line numbers
                foreach ($files as $candidate) {
                    $this->lines[$candidate] = null;
                }

                return;
            }

            $missing = [];

            foreach ($files as $candidate) {
                $lines = $this->linesFor($candidate, $dumped);

                if ($lines === null) {
                    $missing[] = $candidate;

                    continue;
                }

                $this->lines[$candidate] = $lines;
            }

            if ($missing === $files) {
                // The first file ended the process before any file was compiled
                $this->lines[array_shift($missing)] = null;
            }

            $files = $missing;
        }
    }

    /**
     * @param array<non-empty-string, array<positive-int, true>> $dumped
     *
     * @return ?array<positive-int, true>
     */
    private function linesFor(string $file, array $dumped): ?array
    {
        if (array_key_exists($file, $dumped)) {
            return $dumped[$file];
        }

        $realPath = realpath($file);

        if ($realPath !== false && array_key_exists($realPath, $dumped)) {
            return $dumped[$realPath];
        }

        return null;
    }

    /**
     * @param non-empty-list<string> $files
     *
     * @return ?array<non-empty-string, array<positive-int, true>> null when the dump does not have line numbers
     */
    private function dump(array $files): ?array
    {
        $listOfFiles = tempnam(sys_get_temp_dir(), 'php-code-coverage-bytecode-');

        if ($listOfFiles === false) {
            // @codeCoverageIgnoreStart
            return [];
            // @codeCoverageIgnoreEnd
        }

        try {
            if (file_put_contents($listOfFiles, implode("\n", $files)) === false) {
                // @codeCoverageIgnoreStart
                return [];
                // @codeCoverageIgnoreEnd
            }

            $nullDevice = PHP_OS_FAMILY === 'Windows' ? 'NUL' : '/dev/null';

            $process = @proc_open(
                [
                    $this->phpBinary,
                    '-n',
                    '-d', 'opcache.enable=1',
                    '-d', 'opcache.enable_cli=1',
                    '-d', 'opcache.file_update_protection=0',
                    '-d', 'opcache.max_accelerated_files=1000000',
                    '-d', 'opcache.jit=disable',
                    '-d', 'opcache.optimization_level=0',
                    '-d', 'opcache.opt_debug_level=' . self::DEBUG_LEVEL,
                    '-d', 'short_open_tag=' . (filter_var(ini_get('short_open_tag'), FILTER_VALIDATE_BOOLEAN) ? '1' : '0'),
                    '-d', 'display_errors=0',
                    '-d', 'log_errors=0',
                    '-r', self::COMPILE,
                    $listOfFiles,
                ],
                [
                    0 => ['file', $nullDevice, 'r'],
                    1 => ['file', $nullDevice, 'w'],
                    2 => ['pipe', 'w'],
                ],
                $pipes,
            );

            if (!is_resource($process) || !isset($pipes[2]) || !is_resource($pipes[2])) {
                return [];
            }

            $dumped = (new BytecodeDumpParser)->parse($this->linesOf($pipes[2]));

            fclose($pipes[2]);
            proc_close($process);

            return $dumped;
        } finally {
            @unlink($listOfFiles);
        }
    }

    /**
     * @param resource $stream
     *
     * @return Generator<string>
     */
    private function linesOf($stream): Generator
    {
        while (($line = fgets($stream)) !== false) {
            yield rtrim($line, "\r\n");
        }
    }
}
