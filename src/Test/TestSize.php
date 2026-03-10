<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\Test;

/**
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for phpunit/php-code-coverage
 */
enum TestSize: string
{
    public function isKnown(): bool
    {
        return $this !== self::Unknown;
    }

    public function isUnknown(): bool
    {
        return $this === self::Unknown;
    }

    public function isSmall(): bool
    {
        return $this === self::Small;
    }

    public function isMedium(): bool
    {
        return $this === self::Medium;
    }

    public function isLarge(): bool
    {
        return $this === self::Large;
    }

    public function isGreaterThan(self $other): bool
    {
        return $this->order() > $other->order();
    }

    public function asString(): string
    {
        return $this->value;
    }

    private function order(): int
    {
        return match ($this) {
            self::Unknown => -1,
            self::Small   => 0,
            self::Medium  => 1,
            self::Large   => 2,
        };
    }

    case Unknown = 'unknown';
    case Small   = 'small';
    case Medium  = 'medium';
    case Large   = 'large';
}
