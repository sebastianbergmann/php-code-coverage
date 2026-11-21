<?php declare(strict_types=1);

function row(?int $id, bool $enabled): array
{
    return [
        'active' => $id !== null
            && $enabled,
        'visible' => $id !== null
            && $enabled
            && $id > 0,
    ];
}
