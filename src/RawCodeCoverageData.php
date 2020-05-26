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

use PHP_Token_Stream;
use SebastianBergmann\CodeCoverage\Driver\Driver;

/**
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 */
final class RawCodeCoverageData
{
    /**
     * @var array
     *
     * @see https://xdebug.org/docs/code_coverage for format
     */
    private $lineCoverage = [];

    /**
     * @var array
     *
     * @see https://xdebug.org/docs/code_coverage for format
     */
    private $functionCoverage = [];

    public static function fromXdebugWithoutPathCoverage(array $rawCoverage): self
    {
        return new self($rawCoverage, []);
    }

    public static function fromXdebugWithPathCoverage(array $rawCoverage): self
    {
        $lineCoverage     = [];
        $functionCoverage = [];

        foreach ($rawCoverage as $file => $fileCoverageData) {
            if (!isset($fileCoverageData['functions'])) {
                // Current file does not have functions, so line coverage
                // is stored in $fileCoverageData, not in $fileCoverageData['lines']
                $lineCoverage[$file] = $fileCoverageData;

                continue;
            }

            $lineCoverage[$file]     = $fileCoverageData['lines'];
            $functionCoverage[$file] = $fileCoverageData['functions'];
        }

        return new self($lineCoverage, $functionCoverage);
    }

    public static function fromUncoveredFile(string $filename, PHP_Token_Stream $tokens): self
    {
        $lineCoverage = [];

        $lines     = \file($filename);
        $lineCount = \count($lines);

        for ($i = 1; $i <= $lineCount; $i++) {
            $lineCoverage[$i] = Driver::LINE_NOT_EXECUTED;
        }

        //remove empty lines
        foreach ($lines as $index => $line) {
            if (!\trim($line)) {
                unset($lineCoverage[$index + 1]);
            }
        }

        //not all lines are actually executable though, remove these
        try {
            foreach ($tokens->getInterfaces() as $interface) {
                $interfaceStartLine = $interface['startLine'];
                $interfaceEndLine   = $interface['endLine'];

                foreach (\range($interfaceStartLine, $interfaceEndLine) as $line) {
                    unset($lineCoverage[$line]);
                }
            }

            foreach (\array_merge($tokens->getClasses(), $tokens->getTraits()) as $classOrTrait) {
                $classOrTraitStartLine = $classOrTrait['startLine'];
                $classOrTraitEndLine   = $classOrTrait['endLine'];

                if (empty($classOrTrait['methods'])) {
                    foreach (\range($classOrTraitStartLine, $classOrTraitEndLine) as $line) {
                        unset($lineCoverage[$line]);
                    }

                    continue;
                }

                $firstMethod          = \array_shift($classOrTrait['methods']);
                $firstMethodStartLine = $firstMethod['startLine'];
                $lastMethodEndLine    = $firstMethod['endLine'];

                do {
                    $lastMethod = \array_pop($classOrTrait['methods']);
                } while ($lastMethod !== null && 0 === \strpos($lastMethod['signature'], 'anonymousFunction'));

                if ($lastMethod !== null) {
                    $lastMethodEndLine = $lastMethod['endLine'];
                }

                foreach (\range($classOrTraitStartLine, $firstMethodStartLine) as $line) {
                    unset($lineCoverage[$line]);
                }

                foreach (\range($lastMethodEndLine + 1, $classOrTraitEndLine) as $line) {
                    unset($lineCoverage[$line]);
                }
            }

            foreach ($tokens->tokens() as $token) {
                switch (\get_class($token)) {
                    case \PHP_Token_COMMENT::class:
                    case \PHP_Token_DOC_COMMENT::class:
                        $_token = \trim((string) $token);
                        $_line  = \trim($lines[$token->getLine() - 1]);

                        $start = $token->getLine();
                        $end   = $start + \substr_count((string) $token, "\n");

                        // Do not ignore the first line when there is a token
                        // before the comment
                        if (0 !== \strpos($_token, $_line)) {
                            $start++;
                        }

                        for ($i = $start; $i < $end; $i++) {
                            unset($lineCoverage[$i]);
                        }

                        // A DOC_COMMENT token or a COMMENT token starting with "/*"
                        // does not contain the final \n character in its text
                        if (isset($lines[$i - 1]) && 0 === \strpos($_token, '/*') && '*/' === \substr(\trim($lines[$i - 1]), -2)) {
                            unset($lineCoverage[$i]);
                        }

                        break;

                    /* @noinspection PhpMissingBreakStatementInspection */
                    case \PHP_Token_NAMESPACE::class:
                        unset($lineCoverage[$token->getEndLine()]);

                    // Intentional fallthrough

                    case \PHP_Token_INTERFACE::class:
                    case \PHP_Token_TRAIT::class:
                    case \PHP_Token_CLASS::class:
                    case \PHP_Token_FUNCTION::class:
                    case \PHP_Token_DECLARE::class:
                    case \PHP_Token_OPEN_TAG::class:
                    case \PHP_Token_CLOSE_TAG::class:
                    case \PHP_Token_USE::class:
                    case \PHP_Token_USE_FUNCTION::class:
                        unset($lineCoverage[$token->getLine()]);

                        break;
                }
            }
        } catch (\Exception $e) { // This can happen with PHP_Token_Stream if the file is syntactically invalid
            // do nothing
        }

        return new self([$filename => $lineCoverage], []);
    }

    private function __construct(array $lineCoverage, array $functionCoverage)
    {
        $this->lineCoverage     = $lineCoverage;
        $this->functionCoverage = $functionCoverage;
    }

    public function clear(): void
    {
        $this->lineCoverage = $this->functionCoverage = [];
    }

    public function getLineCoverage(): array
    {
        return $this->lineCoverage;
    }

    public function getFunctionCoverage(): array
    {
        return $this->functionCoverage;
    }

    public function removeCoverageDataForFile(string $filename): void
    {
        unset($this->lineCoverage[$filename], $this->functionCoverage[$filename]);
    }

    /**
     * @param int[] $lines
     */
    public function keepCoverageDataOnlyForLines(string $filename, array $lines): void
    {
        $this->lineCoverage[$filename] = \array_intersect_key(
            $this->lineCoverage[$filename],
            \array_flip($lines)
        );
    }

    /**
     * @param int[] $lines
     */
    public function removeCoverageDataForLines(string $filename, array $lines): void
    {
        if (empty($lines)) {
            return;
        }

        $this->lineCoverage[$filename] = \array_diff_key(
            $this->lineCoverage[$filename],
            \array_flip($lines)
        );

        if (isset($this->functionCoverage[$filename])) {
            foreach ($this->functionCoverage[$filename] as $functionName => $functionData) {
                foreach ($functionData['branches'] as $branchId => $branch) {
                    if (\count(\array_intersect($lines, \range($branch['line_start'], $branch['line_end']))) > 0) {
                        unset($this->functionCoverage[$filename][$functionName]['branches'][$branchId]);

                        foreach ($functionData['paths'] as $pathId => $path) {
                            if (\in_array($branchId, $path['path'], true)) {
                                unset($this->functionCoverage[$filename][$functionName]['paths'][$pathId]);
                            }
                        }
                    }
                }
            }
        }
    }
}
