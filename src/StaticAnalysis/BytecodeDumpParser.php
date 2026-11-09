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

use function array_key_last;
use function array_pop;
use function ksort;
use function preg_match;

/**
 * Determines the executable lines of files from OPcache's dump of their
 * bytecode before it is optimized, with line numbers
 * (opcache.opt_debug_level=0x40010000, available as of PHP 8.6).
 *
 * A line is executable when at least one opcode is attributed to it that
 *
 * - is not ignored by a driver that collects code coverage from bytecode:
 *   Xdebug and PCOV do not report lines for some opcodes (RECV for the
 *   parameters of a function, for instance), and a line is only executable
 *   when every such driver reports it when it is executed
 * - is not structural (GENERATOR_CREATE on the signature line of a generator)
 * - is not the return that the compiler appends to every function and file
 *   on its last line (the implicit return), or the check that a function
 *   whose return type is never does not return, which the compiler appends
 *   to such a function in place of the implicit return
 *
 * The implicit return of a function, method, or closure whose body is empty
 * is the only code of that function, so its line is executable.
 *
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for phpunit/php-code-coverage
 */
final class BytecodeDumpParser
{
    /**
     * The opcodes for which Xdebug or PCOV do not report a line.
     */
    private const array IGNORED_OPCODES = [
        'ASSERT_CHECK'          => true,
        'BIND_GLOBAL'           => true,
        'DECLARE_ANON_CLASS'    => true,
        'DECLARE_CLASS'         => true,
        'DECLARE_CLASS_DELAYED' => true,
        'DECLARE_CONST'         => true,
        'DECLARE_FUNCTION'      => true,
        'EXT_FCALL_BEGIN'       => true,
        'EXT_FCALL_END'         => true,
        'EXT_NOP'               => true,
        'EXT_STMT'              => true,
        'FAST_CALL'             => true,
        'FAST_RET'              => true,
        'FE_FREE'               => true,
        'FREE'                  => true,
        'NOP'                   => true,
        'OP_DATA'               => true,
        'RECV'                  => true,
        'RECV_INIT'             => true,
        'RECV_VARIADIC'         => true,
        'SEND_REF'              => true,
        'SEND_UNPACK'           => true,
        'SEND_VAL'              => true,
        'SEND_VAR_EX'           => true,
        'TICKS'                 => true,
        'VERIFY_RETURN_TYPE'    => true,
    ];

    private const array STRUCTURAL_OPCODES = [
        'GENERATOR_CREATE' => true,
    ];

    /**
     * The opcodes that the compiler appends to every function and file on its
     * last line.
     */
    private const array IMPLICIT_RETURN_OPCODES = [
        'GENERATOR_RETURN'  => true,
        'RETURN'            => true,
        'RETURN_BY_REF'     => true,
        'VERIFY_NEVER_TYPE' => true,
    ];

    /**
     * The opcodes that a function with an empty body consists of, apart from
     * its implicit return.
     */
    private const array EMPTY_BODY_OPCODES = [
        'EXT_NOP'            => true,
        'EXT_STMT'           => true,
        'NOP'                => true,
        'RECV'               => true,
        'RECV_INIT'          => true,
        'RECV_VARIADIC'      => true,
        'VERIFY_RETURN_TYPE' => true,
    ];

    /**
     * @param iterable<string> $dump the lines of the dump, without line breaks
     *
     * @return ?array<non-empty-string, array<positive-int, true>> the executable lines of each file whose bytecode is in the dump, null when the dump does not have line numbers (PHP < 8.6)
     */
    public function parse(iterable $dump): ?array
    {
        $result  = [];
        $name    = '';
        $opArray = null;

        foreach ($dump as $line) {
            if (preg_match('/^L(\d+) \d+ (?:\S+ = )?([A-Z_]+)/', $line, $matches) === 1) {
                $lineNumber = (int) $matches[1];

                if ($opArray !== null && $lineNumber > 0) {
                    $opArray['oplines'][] = ['line' => $lineNumber, 'opcode' => $matches[2]];
                }

                continue;
            }

            if (preg_match('/^\d+ [A-Z_]+/', $line) === 1) {
                return null;
            }

            if (preg_match('/^\s+; (.+):(\d+)-(\d+)$/', $line, $matches) === 1) {
                if ($opArray !== null) {
                    $result[$opArray['file']] = ($result[$opArray['file']] ?? []) + $this->executableLines($opArray);
                }

                $opArray = [
                    'file'    => $matches[1],
                    'name'    => $name,
                    'end'     => (int) $matches[3],
                    'oplines' => [],
                ];

                continue;
            }

            if ($line !== 'LIVE RANGES:' &&
                $line !== 'EXCEPTION TABLE:' &&
                preg_match('/^(\S.*):$/', $line, $matches) === 1) {
                $name = $matches[1];
            }
        }

        if ($opArray !== null) {
            $result[$opArray['file']] = ($result[$opArray['file']] ?? []) + $this->executableLines($opArray);
        }

        foreach ($result as &$lines) {
            ksort($lines);
        }

        return $result;
    }

    /**
     * @param array{file: non-empty-string, name: string, end: int, oplines: list<array{line: positive-int, opcode: non-empty-string}>} $opArray
     *
     * @return array<positive-int, true>
     */
    private function executableLines(array $opArray): array
    {
        $oplines            = $opArray['oplines'];
        $implicitReturnLine = null;
        $last               = array_key_last($oplines);

        if ($last !== null &&
            isset(self::IMPLICIT_RETURN_OPCODES[$oplines[$last]['opcode']]) &&
            $oplines[$last]['line'] === $opArray['end']) {
            $implicitReturnLine = $oplines[$last]['line'];

            array_pop($oplines);
        }

        $lines     = [];
        $emptyBody = true;

        foreach ($oplines as $opline) {
            if (!isset(self::EMPTY_BODY_OPCODES[$opline['opcode']])) {
                $emptyBody = false;
            }

            if (isset(self::IGNORED_OPCODES[$opline['opcode']]) || isset(self::STRUCTURAL_OPCODES[$opline['opcode']])) {
                continue;
            }

            $lines[$opline['line']] = true;
        }

        if ($lines === [] && $emptyBody && $implicitReturnLine !== null && $this->bodyCanBeEmpty($opArray['name'])) {
            $lines[$implicitReturnLine] = true;
        }

        return $lines;
    }

    /**
     * Whether the op array with the given name is the body of a function,
     * method, or closure; the code of a file ("$_main") and property hooks
     * are not counted as executable when they are empty.
     */
    private function bodyCanBeEmpty(string $name): bool
    {
        if ($name === '$_main') {
            return false;
        }

        return preg_match('/::\$[^:]+::(get|set)$/', $name) !== 1;
    }
}
