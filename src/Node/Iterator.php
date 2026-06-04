<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\Node;

use function assert;
use RecursiveIterator;

/**
 * @template-implements RecursiveIterator<int, AbstractNode>
 *
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for phpunit/php-code-coverage
 */
final class Iterator implements RecursiveIterator
{
    /**
     * @var list<AbstractNode>
     */
    private readonly array $nodes;

    /**
     * @var non-negative-int
     */
    private int $position = 0;

    public function __construct(Directory $node)
    {
        $this->nodes = $node->children();
    }

    public function rewind(): void
    {
        $this->position = 0;
    }

    public function valid(): bool
    {
        return isset($this->nodes[$this->position]);
    }

    /**
     * @return non-negative-int
     */
    public function key(): int
    {
        return $this->position;
    }

    public function current(): ?AbstractNode
    {
        return $this->nodes[$this->position] ?? null;
    }

    public function next(): void
    {
        $this->position++;
    }

    public function getChildren(): self
    {
        $node = $this->nodes[$this->position] ?? null;

        assert($node instanceof Directory);

        return new self($node);
    }

    public function hasChildren(): bool
    {
        return ($this->nodes[$this->position] ?? null) instanceof Directory;
    }
}
