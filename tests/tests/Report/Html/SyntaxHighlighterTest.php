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

use function implode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[CoversClass(SyntaxHighlighter::class)]
#[Small]
final class SyntaxHighlighterTest extends TestCase
{
    public function testHighlightsInlineHtml(): void
    {
        $highlighted = (new SyntaxHighlighter)->highlight(TEST_FILES_PATH . 'source_with_inline_html.php');

        $this->assertStringContainsString('<span class="html">', implode("\n", $highlighted));
    }

    public function testHighlightsFileThatCannotBeParsed(): void
    {
        $highlighted = (new SyntaxHighlighter)->highlight(TEST_FILES_PATH . 'source_that_cannot_be_parsed.php');

        $this->assertCount(5, $highlighted);
        $this->assertStringContainsString('<span class="default">$b</span>', $highlighted[4]);
    }
}
