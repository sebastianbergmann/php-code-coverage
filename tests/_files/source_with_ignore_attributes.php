<?php declare(strict_types=1);
use PHPUnit\Framework\Attributes\CodeCoverageIgnore;

#[CodeCoverageIgnore]
class Foo
{
    public function bar(): void
    {
    }
}

class Bar
{
    #[CodeCoverageIgnore]
    public function foo(): void
    {
    }
}
