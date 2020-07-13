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

use const T_CLASS;
use const T_COMMENT;
use const T_DOC_COMMENT;
use const T_INTERFACE;
use const T_TRAIT;
use function array_merge;
use function array_unique;
use function file_get_contents;
use function is_array;
use function sort;
use function token_get_all;
use function trim;
use PhpParser\Error;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;

final class IgnoredLinesFinder
{
    /**
     * @psalm-var array<string,list<int>>
     */
    private $ignoredLines = [];

    public function findIgnoredLinesInFile(string $filename, bool $useAnnotationsForIgnoringCode, bool $ignoreDeprecatedCode): array
    {
        if (isset($this->ignoredLines[$filename])) {
            return $this->ignoredLines[$filename];
        }

        $this->ignoredLines[$filename] = [];

        $this->findLinesIgnoredByLineBasedAnnotations($filename, $useAnnotationsForIgnoringCode);

        if ($useAnnotationsForIgnoringCode) {
            $this->findLinesIgnoredByDocBlockAnnotations($filename, $ignoreDeprecatedCode);
        }

        $this->ignoredLines[$filename] = array_unique($this->ignoredLines[$filename]);

        sort($this->ignoredLines[$filename]);

        return $this->ignoredLines[$filename];
    }

    private function findLinesIgnoredByLineBasedAnnotations(string $filename, bool $useAnnotationsForIgnoringCode): void
    {
        $ignore = false;
        $stop   = false;

        foreach (token_get_all(file_get_contents($filename)) as $token) {
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

    private function findLinesIgnoredByDocBlockAnnotations(string $filename, bool $ignoreDeprecatedCode): void
    {
        $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);

        try {
            $nodes = $parser->parse(file_get_contents($filename));

            assert($nodes !== null);

            $traverser = new NodeTraverser;
            $visitor   = new IgnoredLinesFindingVisitor($ignoreDeprecatedCode);

            $traverser->addVisitor($visitor);

            /* @noinspection UnusedFunctionResultInspection */
            $traverser->traverse($nodes);

            $this->ignoredLines[$filename] = array_merge(
                $this->ignoredLines[$filename],
                $visitor->ignoredLines()
            );
            // @codeCoverageIgnoreStart
        } catch (Error $error) {
        }
        // @codeCoverageIgnoreEnd
    }
}
