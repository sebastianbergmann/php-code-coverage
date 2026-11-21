<?php declare(strict_types=1);

function arrowFunction(int $n): bool
{
    $check = static fn (int $i): bool => $i > 0 && (
        $i < 10 || $i === 99
    );

    return $check($n);
}

function ternary(bool $a, bool $b): string
{
    return $a
        && $b ? 'abc'
        . 'def'
        : 'x';
}

function matchArm(int $k, bool $a, bool $b): bool
{
    return match ($k) {
        1 => $a && (
            $b || $k === 99
        ),
        default => false,
    };
}
