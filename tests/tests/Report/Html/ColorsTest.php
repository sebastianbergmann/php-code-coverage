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

        $this->assertSame('#dff0d8', $colors->successLow());
        $this->assertSame('#c3e3b5', $colors->successMedium());
        $this->assertSame('#99cb84', $colors->successHigh());
        $this->assertSame('#fcf8e3', $colors->warning());
        $this->assertSame('#f2dede', $colors->danger());
    }

    public function testCanBeCreatedFromCustomValues(): void
    {
        $colors = Colors::from('successLow', 'successMedium', 'successHigh', 'warning', 'danger');

        $this->assertSame('successLow', $colors->successLow());
        $this->assertSame('successMedium', $colors->successMedium());
        $this->assertSame('successHigh', $colors->successHigh());
        $this->assertSame('warning', $colors->warning());
        $this->assertSame('danger', $colors->danger());
    }
}
