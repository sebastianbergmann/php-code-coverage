<?php declare(strict_types=1);

function matchCondition(?int $id, ?bool $enabled): array
{
    return match (true) {
        $id !== null
        && $enabled !== null => [
            'id' => $id,
        ],
        default => [],
    };
}

function ternaryConditionInArrayElement(?int $a, ?int $b): string
{
    return json_encode(['both' => ($a !== null
        && $b !== null)
        ? 'yes'
        : 'no']);
}

function operandsOfOtherOperatorsInArrayElements(?int $a, ?int $b): array
{
    return [
        'not' => !($a !== null
            && $b !== null),
        'or' => $a === 1 || ($a !== null
            && $b !== null),
    ];
}

function staticVariable(?int $a, ?int $b): bool
{
    static $result = $a !== null
        && $b !== null;

    return $result;
}

function closureInArrayElement(?int $a, ?int $b): array
{
    return [
        'closure' => static function () use ($a, $b): bool
        {
            return $a !== null
                && $b !== null;
        },
    ];
}
