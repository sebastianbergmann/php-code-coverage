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
use function array_merge;
use function array_unique;
use function assert;
use function file_get_contents;
use function is_array;
use function max;
use function range;
use function sort;
use function sprintf;
use function substr_count;
use function token_get_all;
use function trim;
use PhpParser\Error;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\NodeVisitor\ParentConnectingVisitor;
use PhpParser\ParserFactory;
use SebastianBergmann\CodeCoverage\ParserException;
use SebastianBergmann\LinesOfCode\LineCountingVisitor;

/**
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @phpstan-import-type LinesType from FileAnalyser
 */
final class ParsingFileAnalyser implements FileAnalyser
{
    /**
     * @var array<string, array<string, Interface_>>
     */
    private array $interfaces = [];

    /**
     * @var array<string, array<string, Class_>>
     */
    private array $classes = [];

    /**
     * @var array<string, array<string, Trait_>>
     */
    private array $traits = [];

    /**
     * @var array<string, array<string, Function_>>
     */
    private array $functions = [];

    /**
     * @var array<string, LinesOfCode>
     */
    private array $linesOfCode = [];

    /**
     * @var array<string, LinesType>
     */
    private array $ignoredLines = [];

    /**
     * @var array<string, LinesType>
     */
    private array $executableLines = [];
    private readonly bool $useAnnotationsForIgnoringCode;
    private readonly bool $ignoreDeprecatedCode;

    public function __construct(bool $useAnnotationsForIgnoringCode, bool $ignoreDeprecatedCode)
    {
        $this->useAnnotationsForIgnoringCode = $useAnnotationsForIgnoringCode;
        $this->ignoreDeprecatedCode          = $ignoreDeprecatedCode;
    }

    /**
     * @return array<string, Interface_>
     */
    public function interfacesIn(string $filename): array
    {
        $this->analyse($filename);

        return $this->interfaces[$filename];
    }

    /**
     * @return array<string, Class_>
     */
    public function classesIn(string $filename): array
    {
        $this->analyse($filename);

        return $this->classes[$filename];
    }

    /**
     * @return array<string, Trait_>
     */
    public function traitsIn(string $filename): array
    {
        $this->analyse($filename);

        return $this->traits[$filename];
    }

    /**
     * @return array<string, Function_>
     */
    public function functionsIn(string $filename): array
    {
        $this->analyse($filename);

        return $this->functions[$filename];
    }

    public function linesOfCodeFor(string $filename): LinesOfCode
    {
        $this->analyse($filename);

        return $this->linesOfCode[$filename];
    }

    /**
     * @return LinesType
     */
    public function executableLinesIn(string $filename): array
    {
        $this->analyse($filename);

        return $this->executableLines[$filename];
    }

    /**
     * @return LinesType
     */
    public function ignoredLinesFor(string $filename): array
    {
        $this->analyse($filename);

        return $this->ignoredLines[$filename];
    }

    /**
     * @throws ParserException
     */
    private function analyse(string $filename): void
    {
        if (isset($this->interfaces[$filename])) {
            return;
        }

        $source      = file_get_contents($filename);
        $linesOfCode = max(substr_count($source, "\n") + 1, substr_count($source, "\r") + 1);

        if ($linesOfCode === 0 && $source !== false && $source !== '') {
            $linesOfCode = 1;
        }

        assert($linesOfCode > 0);

        $parser = (new ParserFactory)->createForHostVersion();

        try {
            $nodes = $parser->parse($source);

            assert($nodes !== null);

            $traverser                     = new NodeTraverser;
            $codeUnitFindingVisitor        = new CodeUnitFindingVisitor($filename);
            $lineCountingVisitor           = new LineCountingVisitor($linesOfCode);
            $ignoredLinesFindingVisitor    = new IgnoredLinesFindingVisitor($this->useAnnotationsForIgnoringCode, $this->ignoreDeprecatedCode);
            $executableLinesFindingVisitor = new ExecutableLinesFindingVisitor($source);

            $traverser->addVisitor(new NameResolver);
            $traverser->addVisitor(new ParentConnectingVisitor);
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
                    $filename,
                    $error->getMessage(),
                ),
                $error->getCode(),
                $error,
            );
        }
        // @codeCoverageIgnoreEnd

        $this->interfaces[$filename]      = $codeUnitFindingVisitor->interfaces();
        $this->classes[$filename]         = $codeUnitFindingVisitor->classes();
        $this->traits[$filename]          = $codeUnitFindingVisitor->traits();
        $this->functions[$filename]       = $codeUnitFindingVisitor->functions();
        $this->executableLines[$filename] = $executableLinesFindingVisitor->executableLinesGroupedByBranch();
        $this->ignoredLines[$filename]    = [];

        $this->findLinesIgnoredByLineBasedAnnotations($filename, $source, $this->useAnnotationsForIgnoringCode);

        $this->ignoredLines[$filename] = array_unique(
            array_merge(
                $this->ignoredLines[$filename],
                $ignoredLinesFindingVisitor->ignoredLines(),
            ),
        );

        sort($this->ignoredLines[$filename]);

        $result = $lineCountingVisitor->result();

        $this->linesOfCode[$filename] = new LinesOfCode(
            $result->linesOfCode(),
            $result->commentLinesOfCode(),
            $result->nonCommentLinesOfCode(),
        );
    }

    private function findLinesIgnoredByLineBasedAnnotations(string $filename, string $source, bool $useAnnotationsForIgnoringCode): void
    {
        if (!$useAnnotationsForIgnoringCode) {
            return;
        }

        $start = false;

        foreach (token_get_all($source) as $token) {
            if (!is_array($token) ||
                !(T_COMMENT === $token[0] || T_DOC_COMMENT === $token[0])) {
                continue;
            }

            $comment = trim($token[1]);

            if ($comment === '// @codeCoverageIgnore' ||
                $comment === '//@codeCoverageIgnore') {
                $this->ignoredLines[$filename][] = $token[2];

                continue;
            }

            if ($comment === '// @codeCoverageIgnoreStart' ||
                $comment === '//@codeCoverageIgnoreStart') {
                $start = $token[2];

                continue;
            }

            if ($comment === '// @codeCoverageIgnoreEnd' ||
                $comment === '//@codeCoverageIgnoreEnd') {
                if (false === $start) {
                    $start = $token[2];
                }

                $this->ignoredLines[$filename] = array_merge(
                    $this->ignoredLines[$filename],
                    range($start, $token[2]),
                );
            }
        }
    }
}
