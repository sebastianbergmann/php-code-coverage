<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\Test\Target;

use function array_merge;
use function array_unique;
use function assert;
use function sort;
use SebastianBergmann\CodeCoverage\InvalidCodeCoverageTargetException;

/**
 * @immutable
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 */
final readonly class Mapper
{
    /**
     * @var array{namespaces: array<non-empty-string, list<positive-int>>, classes: array<non-empty-string, list<positive-int>>, classesThatExtendClass: array<non-empty-string, list<positive-int>>, classesThatImplementInterface: array<non-empty-string, list<positive-int>>, traits: array<non-empty-string, list<positive-int>>, methods: array<non-empty-string, list<positive-int>>, functions: array<non-empty-string, list<positive-int>>}
     */
    private array $map;

    /**
     * @param array{namespaces: array<non-empty-string, list<positive-int>>, classes: array<non-empty-string, list<positive-int>>, classesThatExtendClass: array<non-empty-string, list<positive-int>>, classesThatImplementInterface: array<non-empty-string, list<positive-int>>, traits: array<non-empty-string, list<positive-int>>, methods: array<non-empty-string, list<positive-int>>, functions: array<non-empty-string, list<positive-int>>} $map
     */
    public function __construct(array $map)
    {
        $this->map = $map;
    }

    /**
     * @return array<non-empty-string, list<positive-int>>
     */
    public function map(TargetCollection $targets): array
    {
        $result = [];

        foreach ($targets as $target) {
            foreach ($this->mapTarget($target) as $file => $lines) {
                if (!isset($result[$file])) {
                    $result[$file] = $lines;

                    continue;
                }

                $result[$file] = array_unique(array_merge($result[$file], $lines));

                sort($result[$file]);
            }
        }

        return $result;
    }

    /**
     * @throws InvalidCodeCoverageTargetException
     *
     * @return array<non-empty-string, list<positive-int>>
     */
    private function mapTarget(Target $target): array
    {
        if ($target->isClass()) {
            assert($target instanceof Class_);

            if (!isset($this->map['classes'][$target->asString()])) {
                throw new InvalidCodeCoverageTargetException('Class ' . $target->asString());
            }

            return $this->map['classes'][$target->asString()];
        }

        if ($target->isMethod()) {
            assert($target instanceof Method);

            if (!isset($this->map['methods'][$target->asString()])) {
                throw new InvalidCodeCoverageTargetException('Method ' . $target->asString());
            }

            return $this->map['methods'][$target->asString()];
        }
    }
}
