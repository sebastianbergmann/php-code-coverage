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

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[CoversClass(LinesOfCode::class)]
#[Small]
final class LinesOfCodeTest extends TestCase
{
    public function testHasLinesOfCode(): void
    {
        $this->assertSame(1, $this->linesOfCode()->linesOfCode());
    }

    public function testHasCommentLinesOfCode(): void
    {
        $this->assertSame(2, $this->linesOfCode()->commentLinesOfCode());
    }

    public function testHasNonCommentLinesOfCode(): void
    {
        $this->assertSame(3, $this->linesOfCode()->nonCommentLinesOfCode());
    }

    private function linesOfCode(): LinesOfCode
    {
        return new LinesOfCode(1, 2, 3);
    }
}
