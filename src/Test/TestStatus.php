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
enum TestStatus: string
{
    public function isKnown(): bool
    {
        return $this !== self::Unknown;
    }

    public function isUnknown(): bool
    {
        return $this === self::Unknown;
    }

    public function isSuccess(): bool
    {
        return $this === self::Success;
    }

    public function isFailure(): bool
    {
        return $this === self::Failure;
    }

    public function asString(): string
    {
        return $this->value;
    }
    case Unknown = 'unknown';
    case Success = 'success';
    case Failure = 'failure';
}
