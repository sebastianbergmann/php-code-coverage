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

        $this->assertSame('#d6e6f2', $colors->successLow());
        $this->assertSame('#1e3550', $colors->successLowDark());
        $this->assertSame('#b3d1e8', $colors->successMedium());
        $this->assertSame('#2d4f6e', $colors->successMediumDark());
        $this->assertSame('#8cb4d5', $colors->successHigh());
        $this->assertSame('#2a4a6b', $colors->successHighDark());
        $this->assertSame('#1a73b4', $colors->successBar());
        $this->assertSame('#1560a0', $colors->successBarDark());
        $this->assertSame('#fdf0d5', $colors->warning());
        $this->assertSame('#3d3010', $colors->warningDark());
        $this->assertSame('#e5a100', $colors->warningBar());
        $this->assertSame('#b88a00', $colors->warningBarDark());
        $this->assertSame('#fad4c0', $colors->danger());
        $this->assertSame('#4a2a10', $colors->dangerDark());
        $this->assertSame('#d45500', $colors->dangerBar());
        $this->assertSame('#b54400', $colors->dangerBarDark());
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
