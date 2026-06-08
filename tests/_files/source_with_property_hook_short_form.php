<?php declare(strict_types=1);
final class ShortFormHookSubject
{
    public function __construct(private int $n) {}

    public int $doubled {
        get => $this->n * 2;
    }

    public int $multiLine {
        get => $this->n
            * 3;
    }
}
