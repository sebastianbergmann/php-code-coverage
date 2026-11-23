<?php declare(strict_types=1);
function greet(string $name): string
{
    return sprintf(
        '%s, %s',
        'Hello', // @codeCoverageIgnore
        $name,
    );
}
