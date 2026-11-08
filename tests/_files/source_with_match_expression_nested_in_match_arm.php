<?php declare(strict_types=1);
function jumpTable(string $type, mixed $value): mixed
{
    return match ($type) {
        'bool'  => (bool) $value,
        'array' => match (true) {
            is_array($value) => $value,
            default          => [$value],
        },
        default => $value,
    };
}

function noJumpTable(string $type, mixed $value): mixed
{
    return match ($type) {
        'array' => match (true) {
            is_array($value) => $value,
            default          => [$value],
        },
        default => $value,
    };
}

function jumpTableWithSignedIntegers(int $type, mixed $value): mixed
{
    return match ($type) {
        -1 => null,
        +1 => match (true) {
            is_array($value) => $value,
            default          => [$value],
        },
        default => $value,
    };
}
