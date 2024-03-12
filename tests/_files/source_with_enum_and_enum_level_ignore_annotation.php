<?php declare(strict_types=1);
/**
 * @codeCoverageIgnore
 */
enum TestEnumeration
{
    case SomeCase;

    public function isSomeCase(): bool
    {
        return $this === self::SomeCase;
    }
}
