<?php
namespace SebastianBergmann\CodeCoverage\TestFixture;

class Foo
{
    public function one(): array
    {
        $name = 'Rain';
        $number = 2;

        return [
            $name,
            $name => $number,
            $name . '2' => $number + 2,
            $number,
            'x' => [
                $name,
                2,
            ]
        ];
    }
}
