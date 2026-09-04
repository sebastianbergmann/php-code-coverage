<?php declare(strict_types=1);

final class LongFormHookSubject
{
    public function __construct(private ?string $n) {}

    public string $label {
        get {
            return $this->n !== null
                ? $this->n
                : 'fallback';
        }
    }
}
