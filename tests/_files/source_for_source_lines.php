<?php declare(strict_types=1);
namespace SebastianBergmann\CodeCoverage\TestFixture\SourceLines;

function greet(string $name, ?string $title): string
{
    // A comment
    $greeting = sprintf(
        '%s, %s',
        'Hello',
        $name,
    );

    if ($title !== null &&
        $title !== '') {
        return $title . ' ' . $greeting;
    }

    return $greeting;
}

final class Greeter
{
    private string $greeting = 'Hello';

    public function greet(string $name): string
    {
        $anonymous = new class {
            public function greeting(): string
            {
                return 'Hello';
            }
        };

        return $anonymous->greeting() . ', ' . $name;
    }
}

trait Greeting
{
    public function greeting(): string
    {
        return 'Hello';
    }
}

enum Salutation
{
    case Hello;

    public function text(): string
    {
        return match ($this) {
            self::Hello => 'Hello',
        };
    }
}
