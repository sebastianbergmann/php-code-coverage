<?php

// Enum
enum BasicSuit
{
    case Hearts;
    case Diamonds;
    case Clubs;
    case Spades;

    public function shape(): string
    {
        return "Rectangle";         // +1
    }
}

enum BackedSuit: string
{
    case Hearts = 'H';
    case Diamonds = 'D';
    case Clubs = 'C';
    case Spades = 'S';
}

BasicSuit::Diamonds->shape();       // +1
BackedSuit::Clubs;                  // +1


// Intersection types
interface MyIntersection
{
    public function check(MyIntOne&MyIntTwo $intersection);
    public function neverReturn(): never;
}
