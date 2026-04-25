<?php declare(strict_types=1);
final class MatchTrueExpr
{
    public string $result;

    public function __construct(int $value)
    {
        $this->result = match (true) {
            $value < 0   => 'negative',
            $value === 0 => 'zero',
            $value < 10  => 'small',
            default      => 'large',
        };
    }
}
