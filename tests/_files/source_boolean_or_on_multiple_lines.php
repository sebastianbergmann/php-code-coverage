<?php declare(strict_types=1);

function assignment(?array $x): bool
{
    $result = $x === null
        || count($x) === 0
        || $x[0] === 'a';

    return $result;
}

function callArgument(?int $a, bool $b): bool
{
    $result = boolval(
        $a === null
        || $b
    );

    return $result;
}

function arrayElement(bool $a, bool $b): array
{
    return [
        'key' => $a
            || $b,
    ];
}

function variableInMatchArm(bool $flag, bool $a, bool $b): bool
{
    return match ($flag) {
        true => $a
            || $b,
        default => false,
    };
}

function comparisonInMatchArm(bool $flag, ?int $a, bool $b): bool
{
    return match ($flag) {
        true => $a === null
            || $b,
        default => false,
    };
}
