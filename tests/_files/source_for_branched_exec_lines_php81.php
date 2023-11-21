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

// New in initializers
class NewInInit_NoInit
{
    public function __construct(private DateTimeInterface $dateTime) {
    }                                                                                   // +3
    public function noinit(DateTimeInterface $dateTime) {
    }                                                                                   // +1
}
class NewInInit_OneLineNewLine
{
    public function __construct(private DateTimeInterface $dateTime = new DateTime()) {
    }                                                                                   // +1
    public function onelinenewline(DateTimeInterface $dateTime = new DateTime()) {
    }                                                                                   // +2
}
class NewInInit_OneLineSameLine
{
    public function __construct(private DateTimeInterface $dateTime = new DateTime()) {}    // +2
    public function onelinesameline(DateTimeInterface $dateTime = new DateTime()) {}    // +1
}
class NewInInit_MultiLine
{
    public function __construct(
        private
        DateTimeInterface
        $dateTime
        =
        new
        DateTime()
        ,
        private
        bool
        $var
        =
        true
    )
    {
    }                                                                                   // +1
    public function multiline(
        DateTimeInterface $dateTime = new DateTime()
    ) {
    }                                                                                   // +2
}
function newInInit_OneLineNewLine(DateTimeInterface $dateTime = new DateTime()) {
} // +2
function newInInit_OneLineSameLine(DateTimeInterface $dateTime = new DateTime()) {} // +2
function newInInit_multiline(
    DateTimeInterface $dateTime = new DateTime()
) {
} // +1
