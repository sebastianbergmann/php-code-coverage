<?php declare(strict_types=1);
final class HookWithBranchesSubject
{
    public int $value {
        get {
            return $this->value;
        }
        set {
            if ($value % 2 === 0) {
                $this->value = $value / 2;
            } else {
                $this->value = $value;
            }
        }
    }
}
