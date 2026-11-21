<?php declare(strict_types=1);

function toArray(bool $condition, bool $enabled): array
{
    return [
        'condition' => $condition,
        ...$condition
            && $enabled ? [
                'enabled' => $enabled,
            ] : [],
    ];
}
