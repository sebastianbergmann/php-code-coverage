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

use function array_unique;
use function assert;
use function file_get_contents;
use function is_array;
use function sprintf;
use function substr_count;
use function token_get_all;
use function trim;
use PhpParser\Error;
use PhpParser\Lexer;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\NodeVisitor\ParentConnectingVisitor;
use PhpParser\ParserFactory;
use SebastianBergmann\CodeCoverage\ParserException;
use SebastianBergmann\LinesOfCode\LineCountingVisitor;
use SebastianBergmann\LinesOfCode\LinesOfCode;

final class ParsingCoveredFileAnalyser implements CoveredFileAnalyser
{
    /**
     * @var array
     */
    private $classes = [];

    /**
     * @var array
     */
    private $traits = [];

    /**
     * @var array
     */
    private $functions = [];

    /**
     * @var LinesOfCode[]
     */
    private $linesOfCode = [];

    /**
     * @var array
     */
    private $ignoredLines = [];

    /**
     * @var bool
     */
    private $useAnnotationsForIgnoringCode;

    /**
     * @var bool
     */
    private $ignoreDeprecatedCode;

    public function __construct(bool $useAnnotationsForIgnoringCode, bool $ignoreDeprecatedCode)
    {
        $this->useAnnotationsForIgnoringCode = $useAnnotationsForIgnoringCode;
        $this->ignoreDeprecatedCode          = $ignoreDeprecatedCode;
    }

    public function classesIn(string $filename): array
    {
        $this->analyse($filename);

        return $this->classes[$filename];
    }

    public function traitsIn(string $filename): array
    {
        $this->analyse($filename);

        return $this->traits[$filename];
    }

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
        if (isset($this->classes[$filename])) {
            return;
        }

        $source      = file_get_contents($filename);
        $linesOfCode = substr_count($source, "\n");

        $parser = (new ParserFactory)->create(
            ParserFactory::PREFER_PHP7,
            new Lexer
        );

        try {
            $nodes = $parser->parse($source);

            assert($nodes !== null);

            $traverser              = new NodeTraverser;
            $codeUnitFindingVisitor = new CodeUnitFindingVisitor;
            $lineCountingVisitor    = new LineCountingVisitor($linesOfCode);

            $traverser->addVisitor(new NameResolver);
            $traverser->addVisitor(new ParentConnectingVisitor);
            $traverser->addVisitor($codeUnitFindingVisitor);
            $traverser->addVisitor($lineCountingVisitor);

            if ($this->useAnnotationsForIgnoringCode) {
                $ignoredLinesFindingVisitor = new IgnoredLinesFindingVisitor($this->ignoreDeprecatedCode);

                $traverser->addVisitor($ignoredLinesFindingVisitor);
            }

            /* @noinspection UnusedFunctionResultInspection */
            $traverser->traverse($nodes);
            // @codeCoverageIgnoreStart
        } catch (Error $error) {
            throw new ParserException(
                sprintf(
                    'Cannot parse %s: %s',
                    $filename,
                    $error->getMessage()
                ),
                (int) $error->getCode(),
                $error
            );
        }
        // @codeCoverageIgnoreEnd

        $this->classes[$filename]      = $codeUnitFindingVisitor->classes();
        $this->traits[$filename]       = $codeUnitFindingVisitor->traits();
        $this->functions[$filename]    = $codeUnitFindingVisitor->functions();
        $this->linesOfCode[$filename]  = $lineCountingVisitor->result();
        $this->ignoredLines[$filename] = [];

        $this->findLinesIgnoredByLineBasedAnnotations($filename, $source, $this->useAnnotationsForIgnoringCode);

        if (isset($ignoredLinesFindingVisitor)) {
            $this->ignoredLines[$filename] = array_merge(
                $this->ignoredLines[$filename],
                $ignoredLinesFindingVisitor->ignoredLines()
            );
        }

        $this->ignoredLines[$filename] = array_unique($this->ignoredLines[$filename]);

        sort($this->ignoredLines[$filename]);
    }

    private function findLinesIgnoredByLineBasedAnnotations(string $filename, string $source, bool $useAnnotationsForIgnoringCode): void
    {
        $ignore = false;
        $stop   = false;

        foreach (token_get_all($source) as $token) {
            if (!is_array($token)) {
                continue;
            }

            switch ($token[0]) {
                case T_COMMENT:
                case T_DOC_COMMENT:
                    if (!$useAnnotationsForIgnoringCode) {
                        break;
                    }

                    $comment = trim($token[1]);

                    if ($comment === '// @codeCoverageIgnore' ||
                        $comment === '//@codeCoverageIgnore') {
                        $ignore = true;
                        $stop   = true;
                    } elseif ($comment === '// @codeCoverageIgnoreStart' ||
                        $comment === '//@codeCoverageIgnoreStart') {
                        $ignore = true;
                    } elseif ($comment === '// @codeCoverageIgnoreEnd' ||
                        $comment === '//@codeCoverageIgnoreEnd') {
                        $stop = true;
                    }

                    break;

                case T_INTERFACE:
                case T_TRAIT:
                case T_CLASS:
                    // Workaround for https://bugs.xdebug.org/view.php?id=1798
                    $this->ignoredLines[$filename][] = $token[2];

                    break;
            }

            if ($ignore) {
                $this->ignoredLines[$filename][] = $token[2];

                if ($stop) {
                    $ignore = false;
                    $stop   = false;
                }
            }
        }
    }
}
