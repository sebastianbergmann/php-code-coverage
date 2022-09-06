<?php declare(strict_types=1);
use PHPUnit\Framework\Attributes\CodeCoverageIgnore;

#[CodeCoverageIgnore]
class Foo
{
    public function bar(): bool
    {
        return true;
    }
}

class Bar
{
    #[CodeCoverageIgnore]
    public function foo(): bool
    {
        return false;
    }
}
