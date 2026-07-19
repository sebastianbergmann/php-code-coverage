<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\Data;

use function max;
use NoDiscard;

/**
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @phpstan-import-type TestIndexType from ProcessedCodeCoverageData
 * @phpstan-import-type PathCoverageType from RawCodeCoverageData
 */
final class ProcessedPathCoverageData
{
    /** @var array<int, int> */
    public readonly array $path;

    /** @var array<TestIndexType, positive-int> map of test index to the number of times the test executed the path */
    public array $hit;

    /**
     * @param PathCoverageType $xdebugCoverageData
     */
    public static function fromXdebugCoverage(array $xdebugCoverageData): self
    {
        return new self(
            $xdebugCoverageData['path'],
            [],
        );
    }

    /**
     * @param array<int, int>                    $path
     * @param array<TestIndexType, positive-int> $hit
     */
    public function __construct(
        array $path,
        array $hit,
    ) {
        $this->hit  = $hit;
        $this->path = $path;
    }

    /**
     * Hit counts for a test case that occurs in both operands are combined with max(), not summed,
     * because the same test case id on both sides means the same test execution was observed twice.
     *
     * @see ProcessedCodeCoverageData::merge()
     */
    #[NoDiscard]
    public function merge(self $data): self
    {
        if ($data->hit === []) {
            return $this;
        }

        $hit = $this->hit;

        foreach ($data->hit as $testIndex => $count) {
            $hit[$testIndex] = max($hit[$testIndex] ?? 0, $count);
        }

        return new self(
            $this->path,
            $hit,
        );
    }

    /**
     * @param array<TestIndexType, TestIndexType> $remap
     */
    #[NoDiscard]
    public function withRemappedTestIndexes(array $remap): self
    {
        if ($this->hit === []) {
            return $this;
        }

        $hit = [];

        foreach ($this->hit as $testIndex => $count) {
            $hit[$remap[$testIndex] ?? $testIndex] = $count;
        }

        return new self(
            $this->path,
            $hit,
        );
    }

    /**
     * @param TestIndexType $testIndex
     * @param positive-int  $count
     */
    public function recordHit(int $testIndex, int $count): void
    {
        $this->hit[$testIndex] = ($this->hit[$testIndex] ?? 0) + $count;
    }
}
