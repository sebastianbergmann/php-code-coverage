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

/**
 * @phpstan-type LinesType array<positive-int, int>
 *
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for phpunit/php-code-coverage
 */
final readonly class AnalysisResult
{
    /**
     * @var array<string, Interface_>
     */
    private array $interfaces;

    /**
     * @var array<string, Class_>
     */
    private array $classes;

    /**
     * @var array<string, Trait_>
     */
    private array $traits;

    /**
     * @var array<string, Function_>
     */
    private array $functions;
    private LinesOfCode $linesOfCode;

    /**
     * @var LinesType
     */
    private array $executableLines;

    /**
     * @var LinesType
     */
    private array $executableLinesOfMultiLineBranches;

    /**
     * @var array<positive-int, true>
     */
    private array $branchOperatorLines;

    /**
     * @var array<positive-int, true>
     */
    private array $deadLines;

    /**
     * @var array<int, true>
     */
    private array $ignoredLines;

    /**
     * @var ?non-empty-string
     */
    private ?string $parseError;

    /**
     * @param array<string, Interface_> $interfaces
     * @param array<string, Class_>     $classes
     * @param array<string, Trait_>     $traits
     * @param array<string, Function_>  $functions
     * @param LinesType                 $executableLines
     * @param array<positive-int, true> $branchOperatorLines
     * @param array<positive-int, true> $deadLines
     * @param array<int, true>          $ignoredLines
     * @param ?non-empty-string         $parseError
     */
    public function __construct(array $interfaces, array $classes, array $traits, array $functions, LinesOfCode $linesOfCode, array $executableLines, array $branchOperatorLines, array $deadLines, array $ignoredLines, ?string $parseError = null)
    {
        $this->interfaces          = $interfaces;
        $this->classes             = $classes;
        $this->traits              = $traits;
        $this->functions           = $functions;
        $this->linesOfCode         = $linesOfCode;
        $this->executableLines     = $executableLines;
        $this->branchOperatorLines = $branchOperatorLines;
        $this->deadLines           = $deadLines;
        $this->ignoredLines        = $ignoredLines;
        $this->parseError          = $parseError;

        $branches          = [];
        $multiLineBranches = [];

        foreach ($executableLines as $branch) {
            if (isset($branches[$branch])) {
                $multiLineBranches[$branch] = true;
            }

            $branches[$branch] = true;
        }

        $executableLinesOfMultiLineBranches = [];

        foreach ($executableLines as $line => $branch) {
            if (isset($multiLineBranches[$branch])) {
                $executableLinesOfMultiLineBranches[$line] = $branch;
            }
        }

        $this->executableLinesOfMultiLineBranches = $executableLinesOfMultiLineBranches;
    }

    /**
     * @return array<string, Interface_>
     */
    public function interfaces(): array
    {
        return $this->interfaces;
    }

    /**
     * @return array<string, Class_>
     */
    public function classes(): array
    {
        return $this->classes;
    }

    /**
     * @return array<string, Trait_>
     */
    public function traits(): array
    {
        return $this->traits;
    }

    /**
     * @return array<string, Function_>
     */
    public function functions(): array
    {
        return $this->functions;
    }

    public function linesOfCode(): LinesOfCode
    {
        return $this->linesOfCode;
    }

    /**
     * @return LinesType
     */
    public function executableLines(): array
    {
        return $this->executableLines;
    }

    /**
     * The executable lines that share their branch with at least one other
     * line.
     *
     * The status of a line that is the only line of its branch neither depends
     * on nor affects the status of another line, so only these lines have to
     * be considered when the status of a line is propagated to the other lines
     * of its branch (see RawCodeCoverageData::markExecutableLineByBranch()).
     * They are determined once, when a file is analysed, rather than for each
     * test that executes code in that file.
     *
     * @return LinesType
     */
    public function executableLinesOfMultiLineBranches(): array
    {
        return $this->executableLinesOfMultiLineBranches;
    }

    /**
     * @return array<positive-int, true>
     */
    public function branchOperatorLines(): array
    {
        return $this->branchOperatorLines;
    }

    /**
     * @return array<positive-int, true>
     */
    public function deadLines(): array
    {
        return $this->deadLines;
    }

    /**
     * @return array<int, true>
     */
    public function ignoredLines(): array
    {
        return $this->ignoredLines;
    }

    /**
     * @phpstan-assert-if-false !null $this->parseError()
     */
    public function wasParsed(): bool
    {
        return $this->parseError === null;
    }

    /**
     * @return ?non-empty-string
     */
    public function parseError(): ?string
    {
        return $this->parseError;
    }
}
