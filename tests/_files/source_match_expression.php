<?php declare(strict_types=1);
final class MatchExpr
{
    public int $result;

    public function __construct(int $value)
    {
        $this->result = match ($value) {
            0 => 4,
            1 => 5,
            2 => 6,
            3 => 7,
            default => 8,
        };
    }
}
