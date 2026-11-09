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

use function array_intersect_key;

/**
 * Determines the executable lines of a file from its bytecode (see
 * {@see BytecodeLines}) and everything else from the analysis of its syntax.
 *
 * The bytecode states for each line whether code is attributed to it, so each
 * executable line forms a branch of its own: lines do not share the status of
 * other lines (see RawCodeCoverageData::markExecutableLineByBranch()), and no
 * lines are added for operators that the syntax analysis knows are executed
 * (see AnalysisResult::branchOperatorLines()).
 *
 * The analysis of the syntax is used as it is when the file cannot be parsed
 * or compiled.
 *
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for phpunit/php-code-coverage
 */
final readonly class BytecodeSourceAnalyser implements SourceAnalyser
{
    private SourceAnalyser $sourceAnalyser;
    private BytecodeLines $bytecodeLines;

    public function __construct(SourceAnalyser $sourceAnalyser, BytecodeLines $bytecodeLines)
    {
        $this->sourceAnalyser = $sourceAnalyser;
        $this->bytecodeLines  = $bytecodeLines;
    }

    /**
     * @param non-empty-string $sourceCodeFile
     */
    public function analyse(string $sourceCodeFile, string $sourceCode, bool $useAnnotationsForIgnoringCode, bool $ignoreDeprecatedCode): AnalysisResult
    {
        $result = $this->sourceAnalyser->analyse(
            $sourceCodeFile,
            $sourceCode,
            $useAnnotationsForIgnoringCode,
            $ignoreDeprecatedCode,
        );

        if (!$result->wasParsed()) {
            return $result;
        }

        $lines = $this->bytecodeLines->executableLines($sourceCodeFile);

        if ($lines === null) {
            return $result;
        }

        $executableLines = [];

        foreach ($lines as $line => $_) {
            $executableLines[$line] = $line;
        }

        return new AnalysisResult(
            $result->interfaces(),
            $result->classes(),
            $result->traits(),
            $result->functions(),
            $result->linesOfCode(),
            $executableLines,
            [],
            array_intersect_key($result->deadLines(), $lines),
            $result->ignoredLines(),
        );
    }
}
