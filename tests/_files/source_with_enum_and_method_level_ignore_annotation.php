<?php declare(strict_types=1);
enum TestEnumeration
{
    case SomeCase;

    /**
     * @codeCoverageIgnore
     */
    public function isSomeCase(): bool
    {
        return $this === self::SomeCase;
    }
}
