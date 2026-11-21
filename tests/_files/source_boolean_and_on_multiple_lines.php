<?php declare(strict_types=1);

function assignment(?array $x): bool
{
    $result = $x !== null
        && count($x) > 1
        && $x[0] === 'a';

    return $result;
}

function ternaryBranch(bool $flag, ?array $x): bool
{
    $result = $flag
        ? $x !== null
            && count($x) > 1
        : false;

    return $result;
}

function matchArm(int $key, ?array $x): bool
{
    $result = match ($key) {
        1 => $x !== null
            && count($x) > 1,
        default => false,
    };

    return $result;
}

function arrowFunction(?array $x): bool
{
    $check = static fn (?array $x): bool => $x !== null
        && count($x) > 1;

    return $check($x);
}

function callArgument(?array $x): bool
{
    $result = boolval(
        $x !== null
        && count($x) > 1
    );

    return $result;
}

function condition(bool $a, bool $b, bool $c): bool
{
    if ($a ||
        $b && $c) {
        return true;
    }

    return false;
}
