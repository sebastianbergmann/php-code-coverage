<?php declare(strict_types=1);

function toArray(bool $condition): array
{
    return [
        'condition' => $condition,
        ...$condition ? [
            'condition' => $condition,
        ] : [],
    ];
}
