<?php declare(strict_types=1);
abstract class HookAccount
{
    abstract public string $owner { get; set; }
}
final class HookPerson
{
    public string $first = '' {
        get => strtoupper($this->first);
        set(string $value) {
            $this->first = trim($value);
        }
    }

    public string $last = '' {
        get {
            return strtoupper($this->last);
        }
    }

    public string $title = '' {
        set(string $value) {
            $this->title = $value;
            /** end of setter */
        }
    }
}
