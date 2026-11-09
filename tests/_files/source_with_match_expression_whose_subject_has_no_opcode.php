<?php declare(strict_types=1);
function classConstants(string $status): int
{
    return match ($status) {
        Status::A => 1,
        Status::B => 2,
        default   => 0,
    };
}

function literals(string $status): int
{
    return match ($status) {
        'a'     => 1,
        'b'     => 2,
        default => 0,
    };
}

function assignment(string $status): int
{
    $label = match ($status) {
        Status::A => 1,
        Status::B => 2,
        default   => 0,
    };

    return $label;
}

function propertyFetch(object $object): int
{
    return match ($object->status) {
        Status::A => 1,
        Status::B => 2,
        default   => 0,
    };
}
