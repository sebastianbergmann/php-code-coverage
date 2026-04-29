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

use const T_COMMENT;
use const T_DOC_COMMENT;
use function array_keys;
use function array_replace;
use function assert;
use function is_array;
use function ksort;
use function max;
use function sprintf;
use function substr_count;
use function token_get_all;
use function trim;
use PhpParser\Error;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use SebastianBergmann\CodeCoverage\ParserException;
use SebastianBergmann\LinesOfCode\LineCountingVisitor;

/**
 * @internal This interface is not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for phpunit/php-code-coverage
 */
final readonly class ParsingSourceAnalyser implements SourceAnalyser
{
    private Parser $parser;

    public function __construct()
    {
        $this->parser = (new ParserFactory)->createForHostVersion();
    }

    /**
     * @param non-empty-string $sourceCodeFile
     */
    public function analyse(string $sourceCodeFile, string $sourceCode, bool $useAnnotationsForIgnoringCode, bool $ignoreDeprecatedCode): AnalysisResult
    {
        $linesOfCode = max(substr_count($sourceCode, "\n") + 1, substr_count($sourceCode, "\r") + 1);

        assert($linesOfCode > 0);

        try {
            $nodes = $this->parser->parse($sourceCode);

            assert($nodes !== null);

            $traverser                     = new NodeTraverser;
            $codeUnitFindingVisitor        = new CodeUnitFindingVisitor($sourceCodeFile);
            $lineCountingVisitor           = new LineCountingVisitor($linesOfCode);
            $ignoredLinesFindingVisitor    = new IgnoredLinesFindingVisitor($useAnnotationsForIgnoringCode, $ignoreDeprecatedCode);
            $executableLinesFindingVisitor = new ExecutableLinesFindingVisitor($sourceCode);

            $traverser->addVisitor(new NameResolver);
            $traverser->addVisitor(new AttributeParentConnectingVisitor);
            $traverser->addVisitor($codeUnitFindingVisitor);
            $traverser->addVisitor($lineCountingVisitor);
            $traverser->addVisitor($ignoredLinesFindingVisitor);
            $traverser->addVisitor($executableLinesFindingVisitor);

            /* @noinspection UnusedFunctionResultInspection */
            $traverser->traverse($nodes);
            // @codeCoverageIgnoreStart
        } catch (Error $error) {
            throw new ParserException(
                sprintf(
                    'Cannot parse %s: %s',
                    $sourceCodeFile,
                    $error->getMessage(),
                ),
                $error->getCode(),
                $error,
            );
        }
        // @codeCoverageIgnoreEnd

        $ignoredLines = array_replace(
            $this->findLinesIgnoredByLineBasedAnnotations(
                $sourceCodeFile,
                $sourceCode,
                $useAnnotationsForIgnoringCode,
            ),
            $ignoredLinesFindingVisitor->ignoredLines(),
        );

        ksort($ignoredLines);

        $ignoredLines = array_keys($ignoredLines);

        return new AnalysisResult(
            $codeUnitFindingVisitor->interfaces(),
            $codeUnitFindingVisitor->classes(),
            $codeUnitFindingVisitor->traits(),
            $codeUnitFindingVisitor->functions(),
            new LinesOfCode(
                $lineCountingVisitor->result()->linesOfCode(),
                $lineCountingVisitor->result()->commentLinesOfCode(),
                $lineCountingVisitor->result()->nonCommentLinesOfCode(),
            ),
            $executableLinesFindingVisitor->executableLinesGroupedByBranch(),
            $executableLinesFindingVisitor->branchOperatorLines(),
            $ignoredLines,
        );
    }

    /**
     * @return array<int, true>
     */
    private function findLinesIgnoredByLineBasedAnnotations(string $filename, string $source, bool $useAnnotationsForIgnoringCode): array
    {
        if (!$useAnnotationsForIgnoringCode) {
            return [];
        }

        $result = [];
        $start  = false;

        foreach (token_get_all($source) as $token) {
            if (!is_array($token) ||
                !(T_COMMENT === $token[0] || T_DOC_COMMENT === $token[0])) {
                continue;
            }

            $annotation = trim($token[1], "/ \n\r\t\0\x0B");

            if ($annotation === '@codeCoverageIgnore') {
                $result[$token[2]] = true;

                continue;
            }

            if ($annotation === '@codeCoverageIgnoreStart') {
                $start = $token[2];

                continue;
            }

            if ($annotation === '@codeCoverageIgnoreEnd') {
                if (false === $start) {
                    $start = $token[2];
                }

                for ($line = $start; $line <= $token[2]; $line++) {
                    $result[$line] = true;
                }
            }
        }

        return $result;
    }
}
