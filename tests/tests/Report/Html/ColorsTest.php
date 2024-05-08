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

#[CoversClass(Colors::class)]
#[Small]
final class ColorsTest extends TestCase
{
    public function testCanBeCreatedFromDefaults(): void
    {
        $colors = Colors::default();

        $this->assertSame('rgb(from var(--bs-success) r g b / 0.25)', $colors->successLow());
        $this->assertSame('rgb(from var(--bs-success) r g b / 0.5)', $colors->successMedium());
        $this->assertSame('rgb(from var(--bs-success) r g b / 0.75)', $colors->successHigh());
        $this->assertSame('rgb(from var(--bs-warning) r g b / 0.25)', $colors->warning());
        $this->assertSame('rgb(from var(--bs-danger) r g b / 0.25)', $colors->danger());
        $this->assertSame('', $colors->theme());
    }

    public function testCanBeCreatedFromCustomValues(): void
    {
        $colors = Colors::from('successLow', 'successMedium', 'successHigh', 'warning', 'danger', 'dark');

        $this->assertSame('successLow', $colors->successLow());
        $this->assertSame('successMedium', $colors->successMedium());
        $this->assertSame('successHigh', $colors->successHigh());
        $this->assertSame('warning', $colors->warning());
        $this->assertSame('danger', $colors->danger());
        $this->assertSame('dark', $colors->theme());
    }
}
