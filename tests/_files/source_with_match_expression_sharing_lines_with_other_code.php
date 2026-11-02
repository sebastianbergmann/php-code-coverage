<?php declare(strict_types=1);
function classes(array $values): array
{
    return array_filter(array_map(static fn (mixed $value): ?string => match (true) {
        is_object($value) => $value::class,
        default           => null,
    }, $values), static fn (?string $class): bool => $class !== null);
}

function label(int $value): string
{
    return match (true) {
        $value < 0 => 'negative',
        default    => 'other',
    };
}
