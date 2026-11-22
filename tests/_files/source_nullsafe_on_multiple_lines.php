<?php declare(strict_types=1);

function methodCalls(?ArrayObject $object): ?int
{
    return $object
        ?->getIterator()
        ?->count();
}

function propertyFetches(?stdClass $object): mixed
{
    $result = $object
        ?->next
        ?->value;

    return $result;
}

function methodCallWithArguments(?ArrayObject $object, string $key): ?bool
{
    $result = $object
        ?->offsetExists(
            $key,
        );

    return $result;
}

function variableInMatchArm(bool $flag, ?ArrayObject $object): ?int
{
    return match ($flag) {
        true => $object
            ?->count(),
        default => null,
    };
}

function methodCallInMatchArm(bool $flag, ?ArrayObject $object): ?int
{
    return match ($flag) {
        true => $object?->getIterator()
            ?->count(),
        default => null,
    };
}

function arrayElement(?ArrayObject $object): array
{
    return [
        'count' => $object?->getIterator()
            ?->count(),
    ];
}

function readWithoutWarnings(?stdClass $object): mixed
{
    $result = $object
        ?->value ?? 'default';

    return $result;
}
