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

use const ENT_DISALLOWED;
use const ENT_NOQUOTES;
use const ENT_XML1;
use function assert;
use function htmlspecialchars;
use function preg_split;
use Generator;
use PhpToken;
use XMLWriter;

/**
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for phpunit/php-code-coverage
 */
final readonly class Source
{
    /**
     * Names for single-character tokens, for which PhpToken::getTokenName()
     * returns the character itself.
     *
     * @var non-empty-array<non-empty-string, non-empty-string>
     */
    private const array SINGLE_CHARACTER_TOKEN_NAMES = [
        '(' => 'T_OPEN_BRACKET',
        ')' => 'T_CLOSE_BRACKET',
        '[' => 'T_OPEN_SQUARE',
        ']' => 'T_CLOSE_SQUARE',
        '{' => 'T_OPEN_CURLY',
        '}' => 'T_CLOSE_CURLY',
        ';' => 'T_SEMICOLON',
        '.' => 'T_DOT',
        ',' => 'T_COMMA',
        '=' => 'T_EQUAL',
        '<' => 'T_LT',
        '>' => 'T_GT',
        '+' => 'T_PLUS',
        '-' => 'T_MINUS',
        '*' => 'T_MULT',
        '/' => 'T_DIV',
        '?' => 'T_QUESTION_MARK',
        '!' => 'T_EXCLAMATION_MARK',
        ':' => 'T_COLON',
        '"' => 'T_DOUBLE_QUOTES',
        '@' => 'T_AT',
        '%' => 'T_PERCENT',
        '|' => 'T_PIPE',
        '$' => 'T_DOLLAR',
        '^' => 'T_CARET',
        '~' => 'T_TILDE',
        '`' => 'T_BACKTICK',
    ];
    private XMLWriter $xmlWriter;

    public function __construct(XMLWriter $xmlWriter)
    {
        $this->xmlWriter = $xmlWriter;
    }

    public function setSourceCode(string $source): void
    {
        $this->xmlWriter->startElement('source');
        $this->xmlWriter->writeAttribute('xmlns', Facade::XML_NAMESPACE);

        if ($source !== '') {
            $this->xmlWriter->startElement('line');
            $this->xmlWriter->writeAttribute('no', '1');

            $currentLine = 1;
            $tokens      = $this->tokens($source);

            foreach ($tokens as [$line, $name, $value]) {
                $currentLine = $this->startLinesUpTo($currentLine, $line);

                $this->xmlWriter->startElement('token');
                $this->xmlWriter->writeAttribute('name', $name);
                $this->xmlWriter->writeRaw(htmlspecialchars($value, ENT_NOQUOTES | ENT_DISALLOWED | ENT_XML1));
                $this->xmlWriter->endElement();
            }

            $this->startLinesUpTo($currentLine, $tokens->getReturn());

            $this->xmlWriter->endElement();
        }

        $this->xmlWriter->endElement();
    }

    /**
     * Yields line number, name, and text of each token, with tokens that span
     * multiple lines split into one token per line, and returns the number of
     * the last line.
     *
     * @return Generator<int, array{int, string, non-empty-string}, void, int>
     */
    private function tokens(string $source): Generator
    {
        $lastLine = 1;

        foreach (PhpToken::tokenize($source) as $token) {
            if (isset(self::SINGLE_CHARACTER_TOKEN_NAMES[$token->text])) {
                yield [$lastLine, self::SINGLE_CHARACTER_TOKEN_NAMES[$token->text], $token->text];

                continue;
            }

            $name = $token->getTokenName();

            assert($name !== null);

            $values = preg_split('/\R/u', $token->text);

            // The text of the token is not valid UTF-8
            if ($values === false) {
                yield [$token->line, $name, '{binary data}'];

                continue;
            }

            foreach ($values as $offset => $value) {
                $lastLine = $token->line + $offset;

                if ($value !== '') {
                    yield [$lastLine, $name, $value];
                }
            }
        }

        return $lastLine;
    }

    private function startLinesUpTo(int $currentLine, int $line): int
    {
        while ($currentLine < $line) {
            $currentLine++;

            $this->xmlWriter->endElement();
            $this->xmlWriter->startElement('line');
            $this->xmlWriter->writeAttribute('no', (string) $currentLine);
        }

        return $currentLine;
    }
}
