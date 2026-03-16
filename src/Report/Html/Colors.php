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

/**
 * @immutable
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for phpunit/php-code-coverage
 */
final readonly class Colors
{
    private string $successLow;
    private string $successLowDark;
    private string $successMedium;
    private string $successMediumDark;
    private string $successHigh;
    private string $successHighDark;
    private string $successBar;
    private string $successBarDark;
    private string $warning;
    private string $warningDark;
    private string $warningBar;
    private string $warningBarDark;
    private string $danger;
    private string $dangerDark;
    private string $dangerBar;
    private string $dangerBarDark;
    private string $breadcrumbs;
    private string $breadcrumbsDark;

    public static function default(): self
    {
        return new self(
            '#d6e6f2',
            '#1e3550',
            '#b3d1e8',
            '#2d4f6e',
            '#8cb4d5',
            '#2a4a6b',
            '#1a73b4',
            '#1560a0',
            '#fdf0d5',
            '#3d3010',
            '#e5a100',
            '#b88a00',
            '#fad4c0',
            '#4a2a10',
            '#d45500',
            '#b54400',
            'var(--bs-gray-200)',
            'var(--bs-gray-800)',
        );
    }

    public static function from(string $successLow, string $successLowDark, string $successMedium, string $successMediumDark, string $successHigh, string $successHighDark, string $successBar, string $successBarDark, string $warning, string $warningDark, string $warningBar, string $warningBarDark, string $danger, string $dangerDark, string $dangerBar, string $dangerBarDark, string $breadcrumbs, string $breadcrumbsDark): self
    {
        return new self(
            $successLow,
            $successLowDark,
            $successMedium,
            $successMediumDark,
            $successHigh,
            $successHighDark,
            $successBar,
            $successBarDark,
            $warning,
            $warningDark,
            $warningBar,
            $warningBarDark,
            $danger,
            $dangerDark,
            $dangerBar,
            $dangerBarDark,
            $breadcrumbs,
            $breadcrumbsDark,
        );
    }

    private function __construct(string $successLow, string $successLowDark, string $successMedium, string $successMediumDark, string $successHigh, string $successHighDark, string $successBar, string $successBarDark, string $warning, string $warningDark, string $warningBar, string $warningBarDark, string $danger, string $dangerDark, string $dangerBar, string $dangerBarDark, string $breadcrumbs, string $breadcrumbsDark)
    {
        $this->successLow        = $successLow;
        $this->successLowDark    = $successLowDark;
        $this->successMedium     = $successMedium;
        $this->successMediumDark = $successMediumDark;
        $this->successHigh       = $successHigh;
        $this->successHighDark   = $successHighDark;
        $this->successBar        = $successBar;
        $this->successBarDark    = $successBarDark;
        $this->warning           = $warning;
        $this->warningDark       = $warningDark;
        $this->warningBar        = $warningBar;
        $this->warningBarDark    = $warningBarDark;
        $this->danger            = $danger;
        $this->dangerDark        = $dangerDark;
        $this->dangerBar         = $dangerBar;
        $this->dangerBarDark     = $dangerBarDark;
        $this->breadcrumbs       = $breadcrumbs;
        $this->breadcrumbsDark   = $breadcrumbsDark;
    }

    public function successLow(): string
    {
        return $this->successLow;
    }

    public function successLowDark(): string
    {
        return $this->successLowDark;
    }

    public function successMedium(): string
    {
        return $this->successMedium;
    }

    public function successMediumDark(): string
    {
        return $this->successMediumDark;
    }

    public function successHigh(): string
    {
        return $this->successHigh;
    }

    public function successHighDark(): string
    {
        return $this->successHighDark;
    }

    public function successBar(): string
    {
        return $this->successBar;
    }

    public function successBarDark(): string
    {
        return $this->successBarDark;
    }

    public function warning(): string
    {
        return $this->warning;
    }

    public function warningDark(): string
    {
        return $this->warningDark;
    }

    public function warningBar(): string
    {
        return $this->warningBar;
    }

    public function warningBarDark(): string
    {
        return $this->warningBarDark;
    }

    public function danger(): string
    {
        return $this->danger;
    }

    public function dangerDark(): string
    {
        return $this->dangerDark;
    }

    public function dangerBar(): string
    {
        return $this->dangerBar;
    }

    public function dangerBarDark(): string
    {
        return $this->dangerBarDark;
    }

    public function breadcrumbs(): string
    {
        return $this->breadcrumbs;
    }

    public function breadcrumbsDark(): string
    {
        return $this->breadcrumbsDark;
    }
}
