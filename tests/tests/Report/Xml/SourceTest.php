<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\Report\Xml;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use XMLWriter;

#[CoversClass(Source::class)]
#[Small]
final class SourceTest extends TestCase
{
    /**
     * @return non-empty-array<non-empty-string, array{non-empty-string, string}>
     */
    public static function sourceCodeProvider(): array
    {
        return [
            'empty source code' => [
                <<<'EOT'
                <source xmlns="https://schema.phpunit.de/coverage/1.0"/>
                EOT,
                '',
            ],

            'source code without trailing line break' => [
                <<<'EOT'
                <source xmlns="https://schema.phpunit.de/coverage/1.0">
                 <line no="1">
                  <token name="T_OPEN_TAG">&lt;?php </token>
                  <token name="T_VARIABLE">$a</token>
                  <token name="T_SEMICOLON">;</token>
                 </line>
                </source>
                EOT,
                '<?php $a;',
            ],

            'consecutive blank lines' => [
                <<<'EOT'
                <source xmlns="https://schema.phpunit.de/coverage/1.0">
                 <line no="1">
                  <token name="T_OPEN_TAG">&lt;?php</token>
                 </line>
                 <line no="2"/>
                 <line no="3"/>
                 <line no="4">
                  <token name="T_VARIABLE">$a</token>
                  <token name="T_SEMICOLON">;</token>
                 </line>
                 <line no="5"/>
                </source>
                EOT,
                "<?php\n\n\n\$a;\n",
            ],

            'trailing blank lines' => [
                <<<'EOT'
                <source xmlns="https://schema.phpunit.de/coverage/1.0">
                 <line no="1">
                  <token name="T_OPEN_TAG">&lt;?php</token>
                 </line>
                 <line no="2">
                  <token name="T_VARIABLE">$a</token>
                  <token name="T_SEMICOLON">;</token>
                 </line>
                 <line no="3"/>
                 <line no="4"/>
                 <line no="5"/>
                </source>
                EOT,
                "<?php\n\$a;\n\n\n",
            ],

            'CRLF line breaks' => [
                <<<'EOT'
                <source xmlns="https://schema.phpunit.de/coverage/1.0">
                 <line no="1">
                  <token name="T_OPEN_TAG">&lt;?php</token>
                 </line>
                 <line no="2"/>
                 <line no="3">
                  <token name="T_VARIABLE">$a</token>
                  <token name="T_SEMICOLON">;</token>
                 </line>
                 <line no="4"/>
                </source>
                EOT,
                "<?php\r\n\r\n\$a;\r\n",
            ],

            'CR line breaks' => [
                <<<'EOT'
                <source xmlns="https://schema.phpunit.de/coverage/1.0">
                 <line no="1">
                  <token name="T_OPEN_TAG">&lt;?php</token>
                 </line>
                 <line no="2"/>
                 <line no="3">
                  <token name="T_VARIABLE">$a</token>
                  <token name="T_SEMICOLON">;</token>
                 </line>
                 <line no="4"/>
                </source>
                EOT,
                "<?php\r\r\$a;\r",
            ],

            'multi-line doc comment' => [
                <<<'EOT'
                <source xmlns="https://schema.phpunit.de/coverage/1.0">
                 <line no="1">
                  <token name="T_OPEN_TAG">&lt;?php</token>
                 </line>
                 <line no="2">
                  <token name="T_DOC_COMMENT">/**</token>
                 </line>
                 <line no="3">
                  <token name="T_DOC_COMMENT"> * a</token>
                 </line>
                 <line no="4">
                  <token name="T_DOC_COMMENT"> */</token>
                 </line>
                 <line no="5"/>
                </source>
                EOT,
                "<?php\n/**\n * a\n */\n",
            ],

            'heredoc with blank line' => [
                <<<'EOT'
                <source xmlns="https://schema.phpunit.de/coverage/1.0">
                 <line no="1">
                  <token name="T_OPEN_TAG">&lt;?php</token>
                 </line>
                 <line no="2">
                  <token name="T_START_HEREDOC">&lt;&lt;&lt;EOT</token>
                 </line>
                 <line no="3">
                  <token name="T_ENCAPSED_AND_WHITESPACE">a</token>
                 </line>
                 <line no="4"/>
                 <line no="5">
                  <token name="T_ENCAPSED_AND_WHITESPACE">b</token>
                 </line>
                 <line no="6">
                  <token name="T_END_HEREDOC">EOT</token>
                  <token name="T_SEMICOLON">;</token>
                 </line>
                 <line no="7"/>
                </source>
                EOT,
                "<?php\n<<<EOT\na\n\nb\nEOT;\n",
            ],

            'inline HTML' => [
                <<<'EOT'
                <source xmlns="https://schema.phpunit.de/coverage/1.0">
                 <line no="1">
                  <token name="T_INLINE_HTML">&lt;p&gt;</token>
                 </line>
                 <line no="2"/>
                 <line no="3">
                  <token name="T_INLINE_HTML">&lt;/p&gt;</token>
                 </line>
                 <line no="4">
                  <token name="T_OPEN_TAG">&lt;?php </token>
                  <token name="T_VARIABLE">$a</token>
                  <token name="T_SEMICOLON">;</token>
                 </line>
                 <line no="5"/>
                </source>
                EOT,
                "<p>\n\n</p>\n<?php \$a;\n",
            ],

            'characters that must be escaped' => [
                <<<'EOT'
                <source xmlns="https://schema.phpunit.de/coverage/1.0">
                 <line no="1">
                  <token name="T_OPEN_TAG">&lt;?php </token>
                  <token name="T_CONSTANT_ENCAPSED_STRING">'&lt;&amp;&gt;'</token>
                  <token name="T_SEMICOLON">;</token>
                 </line>
                 <line no="2"/>
                </source>
                EOT,
                "<?php '<&>';\n",
            ],

            'character that is not allowed in XML' => [
                <<<EOT
                <source xmlns="https://schema.phpunit.de/coverage/1.0">
                 <line no="1">
                  <token name="T_OPEN_TAG">&lt;?php </token>
                  <token name="T_CONSTANT_ENCAPSED_STRING">'\u{FFFD}'</token>
                  <token name="T_SEMICOLON">;</token>
                 </line>
                 <line no="2"/>
                </source>
                EOT,
                "<?php '\x01';\n",
            ],

            'invalid UTF-8' => [
                <<<'EOT'
                <source xmlns="https://schema.phpunit.de/coverage/1.0">
                 <line no="1">
                  <token name="T_OPEN_TAG">&lt;?php </token>
                  <token name="T_CONSTANT_ENCAPSED_STRING">{binary data}</token>
                  <token name="T_SEMICOLON">;</token>
                 </line>
                 <line no="2"/>
                </source>
                EOT,
                "<?php '\xff';\n",
            ],

            'invalid UTF-8 in multi-line token' => [
                <<<'EOT'
                <source xmlns="https://schema.phpunit.de/coverage/1.0">
                 <line no="1">
                  <token name="T_OPEN_TAG">&lt;?php</token>
                 </line>
                 <line no="2">
                  <token name="T_CONSTANT_ENCAPSED_STRING">{binary data}</token>
                  <token name="T_SEMICOLON">;</token>
                 </line>
                 <line no="3"/>
                 <line no="4"/>
                </source>
                EOT,
                "<?php\n'\xff\n';\n",
            ],
        ];
    }

    /**
     * @return non-empty-array<non-empty-string, array{non-empty-string, non-empty-string}>
     */
    public static function singleCharacterTokenProvider(): array
    {
        return [
            'opening parenthesis'           => ['<token name="T_OPEN_BRACKET">(</token>', '<?php f();'],
            'closing parenthesis'           => ['<token name="T_CLOSE_BRACKET">)</token>', '<?php f();'],
            'opening square bracket'        => ['<token name="T_OPEN_SQUARE">[</token>', '<?php $a[0];'],
            'closing square bracket'        => ['<token name="T_CLOSE_SQUARE">]</token>', '<?php $a[0];'],
            'opening curly brace'           => ['<token name="T_OPEN_CURLY">{</token>', '<?php {}'],
            'opening curly brace in string' => ['<token name="T_OPEN_CURLY">{</token>', '<?php "{$a}";'],
            'closing curly brace'           => ['<token name="T_CLOSE_CURLY">}</token>', '<?php {}'],
            'semicolon'                     => ['<token name="T_SEMICOLON">;</token>', '<?php ;'],
            'dot'                           => ['<token name="T_DOT">.</token>', '<?php $a . $b;'],
            'comma'                         => ['<token name="T_COMMA">,</token>', '<?php f($a, $b);'],
            'equals sign'                   => ['<token name="T_EQUAL">=</token>', '<?php $a = $b;'],
            'less-than sign'                => ['<token name="T_LT">&lt;</token>', '<?php $a < $b;'],
            'greater-than sign'             => ['<token name="T_GT">&gt;</token>', '<?php $a > $b;'],
            'plus sign'                     => ['<token name="T_PLUS">+</token>', '<?php $a + $b;'],
            'minus sign'                    => ['<token name="T_MINUS">-</token>', '<?php $a - $b;'],
            'asterisk'                      => ['<token name="T_MULT">*</token>', '<?php $a * $b;'],
            'slash'                         => ['<token name="T_DIV">/</token>', '<?php $a / $b;'],
            'question mark'                 => ['<token name="T_QUESTION_MARK">?</token>', '<?php $a ? $b : $c;'],
            'exclamation mark'              => ['<token name="T_EXCLAMATION_MARK">!</token>', '<?php !$a;'],
            'colon'                         => ['<token name="T_COLON">:</token>', '<?php $a ? $b : $c;'],
            'double quotes'                 => ['<token name="T_DOUBLE_QUOTES">"</token>', '<?php "$a";'],
            'at sign'                       => ['<token name="T_AT">@</token>', '<?php @$a;'],
            'percent sign'                  => ['<token name="T_PERCENT">%</token>', '<?php $a % $b;'],
            'pipe'                          => ['<token name="T_PIPE">|</token>', '<?php $a | $b;'],
            'dollar sign'                   => ['<token name="T_DOLLAR">$</token>', '<?php $$a;'],
            'caret'                         => ['<token name="T_CARET">^</token>', '<?php $a ^ $b;'],
            'tilde'                         => ['<token name="T_TILDE">~</token>', '<?php ~$a;'],
            'backtick'                      => ['<token name="T_BACKTICK">`</token>', '<?php `ls`;'],
        ];
    }

    #[DataProvider('sourceCodeProvider')]
    public function testWritesSourceCodeAsLinesOfTokens(string $expected, string $source): void
    {
        $this->assertSame($expected . "\n", $this->xml($source));
    }

    #[DataProvider('singleCharacterTokenProvider')]
    public function testWritesSingleCharacterTokensWithName(string $expected, string $source): void
    {
        $this->assertStringContainsString($expected, $this->xml($source));
    }

    private function xml(string $source): string
    {
        $xmlWriter = new XMLWriter;

        $xmlWriter->openMemory();
        $xmlWriter->setIndent(true);

        (new Source($xmlWriter))->setSourceCode($source);

        return $xmlWriter->outputMemory();
    }
}
