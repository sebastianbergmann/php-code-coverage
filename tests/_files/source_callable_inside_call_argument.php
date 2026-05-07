<?php declare(strict_types=1);
final class CallableInsideCallArgument
{
    public function run(): string
    {
        $obj = new class(
            static function (): string {
                echo "running\n";

                return match ('a') {
                    'a' => 'a',
                };
            },
        ) {
            public function __construct(public Closure $fn) {}
        };

        return ($obj->fn)();
    }
}
