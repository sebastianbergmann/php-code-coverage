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
        $this->assertSame('#2d4431', $colors->successLowDark());
        $this->assertSame('#c3e3b5', $colors->successMedium());
        $this->assertSame('#3c6051', $colors->successMediumDark());
        $this->assertSame('#99cb84', $colors->successHigh());
        $this->assertSame('#3d5c4e', $colors->successHighDark());
        $this->assertSame('#28a745', $colors->successBar());
        $this->assertSame('#1f8135', $colors->successBarDark());
        $this->assertSame('#fcf8e3', $colors->warning());
        $this->assertSame('#3e3408', $colors->warningDark());
        $this->assertSame('#ffc107', $colors->warningBar());
        $this->assertSame('#c19406', $colors->warningBarDark());
        $this->assertSame('#f2dede', $colors->danger());
        $this->assertSame('#42221e', $colors->dangerDark());
        $this->assertSame('#dc3545', $colors->dangerBar());
        $this->assertSame('#a62633', $colors->dangerBarDark());
        $this->assertSame('var(--bs-gray-200)', $colors->breadcrumbs());
        $this->assertSame('var(--bs-gray-800)', $colors->breadcrumbsDark());
    }

    public function testCanBeCreatedFromCustomValues(): void
    {
        $colors = Colors::from(
            'successLow',
            'successLowDark',
            'successMedium',
            'successMediumDark',
            'successHigh',
            'successHighDark',
            'successBar',
            'successBarDark',
            'warning',
            'warningDark',
            'warningBar',
            'warningBarDark',
            'danger',
            'dangerDark',
            'dangerBar',
            'dangerBarDark',
            'breadcrumbs',
            'breadcrumbsDark',
        );

        $this->assertSame('successLow', $colors->successLow());
        $this->assertSame('successLowDark', $colors->successLowDark());
        $this->assertSame('successMedium', $colors->successMedium());
        $this->assertSame('successMediumDark', $colors->successMediumDark());
        $this->assertSame('successHigh', $colors->successHigh());
        $this->assertSame('successHighDark', $colors->successHighDark());
        $this->assertSame('successBar', $colors->successBar());
        $this->assertSame('successBarDark', $colors->successBarDark());
        $this->assertSame('warning', $colors->warning());
        $this->assertSame('warningDark', $colors->warningDark());
        $this->assertSame('warningBar', $colors->warningBar());
        $this->assertSame('warningBarDark', $colors->warningBarDark());
        $this->assertSame('danger', $colors->danger());
        $this->assertSame('dangerDark', $colors->dangerDark());
        $this->assertSame('dangerBar', $colors->dangerBar());
        $this->assertSame('dangerBarDark', $colors->dangerBarDark());
        $this->assertSame('breadcrumbs', $colors->breadcrumbs());
        $this->assertSame('breadcrumbsDark', $colors->breadcrumbsDark());
    }
}
