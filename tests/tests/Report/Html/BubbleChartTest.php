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

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\CodeCoverage\Report\Thresholds;

#[CoversClass(BubbleChart::class)]
#[Small]
final class BubbleChartTest extends TestCase
{
    public function testHtmlSpecialCharactersInNameAndLinkAreEncoded(): void
    {
        $svg = (new BubbleChart(Thresholds::default()))->render(
            [
                [
                    'name'            => 'Foo<Bar> & Baz',
                    'coverage'        => 50.0,
                    'executableLines' => 10,
                    'complexity'      => 3,
                    'link'            => 'Foo.php.html?a="b"&c',
                ],
            ],
        );

        // The name is rendered as the text of a <title> element.
        $this->assertStringContainsString('Foo&lt;Bar&gt; &amp; Baz', $svg);
        $this->assertStringNotContainsString('Foo<Bar>', $svg);

        // The link is rendered as the value of an href attribute, so the double
        // quote has to be encoded as well.
        $this->assertStringContainsString('href="Foo.php.html?a=&quot;b&quot;&amp;c"', $svg);
        $this->assertStringNotContainsString('a="b"', $svg);
    }
}
