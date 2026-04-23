<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\Report\Html;

use const ENT_COMPAT;
use const ENT_HTML401;
use const ENT_SUBSTITUTE;
use const T_ABSTRACT;
use const T_ARRAY;
use const T_AS;
use const T_BREAK;
use const T_CALLABLE;
use const T_CASE;
use const T_CATCH;
use const T_CLASS;
use const T_CLONE;
use const T_COMMENT;
use const T_CONST;
use const T_CONTINUE;
use const T_DECLARE;
use const T_DEFAULT;
use const T_DO;
use const T_DOC_COMMENT;
use const T_ECHO;
use const T_ELSE;
use const T_ELSEIF;
use const T_EMPTY;
use const T_ENDDECLARE;
use const T_ENDFOR;
use const T_ENDFOREACH;
use const T_ENDIF;
use const T_ENDSWITCH;
use const T_ENDWHILE;
use const T_ENUM;
use const T_EVAL;
use const T_EXIT;
use const T_EXTENDS;
use const T_FINAL;
use const T_FINALLY;
use const T_FN;
use const T_FOR;
use const T_FOREACH;
use const T_FUNCTION;
use const T_GLOBAL;
use const T_GOTO;
use const T_HALT_COMPILER;
use const T_IF;
use const T_IMPLEMENTS;
use const T_INCLUDE;
use const T_INCLUDE_ONCE;
use const T_INLINE_HTML;
use const T_INSTANCEOF;
use const T_INSTEADOF;
use const T_INTERFACE;
use const T_ISSET;
use const T_LIST;
use const T_MATCH;
use const T_NAMESPACE;
use const T_NEW;
use const T_PRINT;
use const T_PRIVATE;
use const T_PRIVATE_SET;
use const T_PROTECTED;
use const T_PROTECTED_SET;
use const T_PUBLIC;
use const T_PUBLIC_SET;
use const T_READONLY;
use const T_REQUIRE;
use const T_REQUIRE_ONCE;
use const T_RETURN;
use const T_STATIC;
use const T_SWITCH;
use const T_THROW;
use const T_TRAIT;
use const T_TRY;
use const T_UNSET;
use const T_USE;
use const T_VAR;
use const T_WHILE;
use const T_YIELD;
use const T_YIELD_FROM;
use const TOKEN_PARSE;
use function count;
use function explode;
use function file_get_contents;
use function htmlspecialchars;
use function is_string;
use function sprintf;
use function str_ends_with;
use function str_replace;
use function token_get_all;
use function trim;

/**
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for phpunit/php-code-coverage
 */
final class SyntaxHighlighter
{
    /**
     * @var array<int,true>
     */
    private const array KEYWORD_TOKENS = [
        T_ABSTRACT      => true,
        T_ARRAY         => true,
        T_AS            => true,
        T_BREAK         => true,
        T_CALLABLE      => true,
        T_CASE          => true,
        T_CATCH         => true,
        T_CLASS         => true,
        T_CLONE         => true,
        T_CONST         => true,
        T_CONTINUE      => true,
        T_DECLARE       => true,
        T_DEFAULT       => true,
        T_DO            => true,
        T_ECHO          => true,
        T_ELSE          => true,
        T_ELSEIF        => true,
        T_EMPTY         => true,
        T_ENDDECLARE    => true,
        T_ENDFOR        => true,
        T_ENDFOREACH    => true,
        T_ENDIF         => true,
        T_ENDSWITCH     => true,
        T_ENDWHILE      => true,
        T_ENUM          => true,
        T_EVAL          => true,
        T_EXIT          => true,
        T_EXTENDS       => true,
        T_FINAL         => true,
        T_FINALLY       => true,
        T_FN            => true,
        T_FOR           => true,
        T_FOREACH       => true,
        T_FUNCTION      => true,
        T_GLOBAL        => true,
        T_GOTO          => true,
        T_HALT_COMPILER => true,
        T_IF            => true,
        T_IMPLEMENTS    => true,
        T_INCLUDE       => true,
        T_INCLUDE_ONCE  => true,
        T_INSTANCEOF    => true,
        T_INSTEADOF     => true,
        T_INTERFACE     => true,
        T_ISSET         => true,
        T_LIST          => true,
        T_MATCH         => true,
        T_NAMESPACE     => true,
        T_NEW           => true,
        T_PRINT         => true,
        T_PRIVATE       => true,
        T_PRIVATE_SET   => true,
        T_PROTECTED     => true,
        T_PROTECTED_SET => true,
        T_PUBLIC        => true,
        T_PUBLIC_SET    => true,
        T_READONLY      => true,
        T_REQUIRE       => true,
        T_REQUIRE_ONCE  => true,
        T_RETURN        => true,
        T_STATIC        => true,
        T_SWITCH        => true,
        T_THROW         => true,
        T_TRAIT         => true,
        T_TRY           => true,
        T_UNSET         => true,
        T_USE           => true,
        T_VAR           => true,
        T_WHILE         => true,
        T_YIELD         => true,
        T_YIELD_FROM    => true,
    ];

    private const int HTML_SPECIAL_CHARS_FLAGS = ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE;

    /**
     * @var array<non-empty-string, list<string>>
     */
    private static array $cache = [];

    /**
     * @param non-empty-string $file
     *
     * @return list<string>
     */
    public function highlight(string $file): array
    {
        if (isset(self::$cache[$file])) {
            return self::$cache[$file];
        }

        $buffer              = file_get_contents($file);
        $tokens              = token_get_all($buffer, TOKEN_PARSE);
        $result              = [''];
        $i                   = 0;
        $stringFlag          = false;
        $fileEndsWithNewLine = str_ends_with($buffer, "\n");

        unset($buffer);

        foreach ($tokens as $j => $token) {
            if (is_string($token)) {
                if ($token === '"' && $tokens[$j - 1] !== '\\') {
                    $result[$i] .= sprintf(
                        '<span class="string">%s</span>',
                        htmlspecialchars($token, self::HTML_SPECIAL_CHARS_FLAGS),
                    );

                    $stringFlag = !$stringFlag;
                } else {
                    $result[$i] .= sprintf(
                        '<span class="keyword">%s</span>',
                        htmlspecialchars($token, self::HTML_SPECIAL_CHARS_FLAGS),
                    );
                }

                continue;
            }

            [$token, $value] = $token;

            $value = str_replace(
                ["\t", ' '],
                ['&nbsp;&nbsp;&nbsp;&nbsp;', '&nbsp;'],
                htmlspecialchars($value, self::HTML_SPECIAL_CHARS_FLAGS),
            );

            if ($value === "\n") {
                $result[++$i] = '';
            } else {
                $lines = explode("\n", $value);

                foreach ($lines as $jj => $line) {
                    $line = trim($line);

                    if ($line !== '') {
                        if ($stringFlag) {
                            $colour = 'string';
                        } else {
                            $colour = 'default';

                            if ($this->isInlineHtml($token)) {
                                $colour = 'html';
                            } elseif ($this->isComment($token)) {
                                $colour = 'comment';
                            } elseif ($this->isKeyword($token)) {
                                $colour = 'keyword';
                            }
                        }

                        $result[$i] .= sprintf(
                            '<span class="%s">%s</span>',
                            $colour,
                            $line,
                        );
                    }

                    if (isset($lines[$jj + 1])) {
                        $result[++$i] = '';
                    }
                }
            }
        }

        if ($fileEndsWithNewLine) {
            unset($result[count($result) - 1]);
        }

        self::$cache[$file] = $result;

        return $result;
    }

    private function isComment(int $token): bool
    {
        return $token === T_COMMENT || $token === T_DOC_COMMENT;
    }

    private function isInlineHtml(int $token): bool
    {
        return $token === T_INLINE_HTML;
    }

    private function isKeyword(int $token): bool
    {
        return isset(self::KEYWORD_TOKENS[$token]);
    }
}
