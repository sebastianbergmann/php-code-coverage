<?php declare(strict_types=1);

function row(?int $id): array
{
    return [
        'actor' => $id !== null
            ? 'user-' . $id
            : null,
        'tags' => $id !== null
            ? [1 => $id]
            : [],
        'name' => $id
            ?? 'anonymous',
    ];
}
