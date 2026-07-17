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
use SebastianBergmann\CodeCoverage\Driver\XdebugDriver;

/**
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @phpstan-import-type TestIdType from ProcessedCodeCoverageData
 * @phpstan-import-type XdebugPathCoverageType from XdebugDriver
 */
final class ProcessedPathCoverageData
{
    /** @var array<int, int> */
    public readonly array $path;

    /** @var array<TestIdType, positive-int> map of test id to the number of times the test executed the path */
    public array $hit;

    /**
     * @param XdebugPathCoverageType $xdebugCoverageData
     */
    public static function fromXdebugCoverage(array $xdebugCoverageData): self
    {
        return new self(
            $xdebugCoverageData['path'],
            [],
        );
    }

    /**
     * @param array<int, int>                 $path
     * @param array<TestIdType, positive-int> $hit
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

        foreach ($data->hit as $testCaseId => $count) {
            $hit[$testCaseId] = max($hit[$testCaseId] ?? 0, $count);
        }

        return new self(
            $this->path,
            $hit,
        );
    }

    /**
     * @param TestIdType   $testCaseId
     * @param positive-int $count
     */
    public function recordHit(string $testCaseId, int $count): void
    {
        $this->hit[$testCaseId] = ($this->hit[$testCaseId] ?? 0) + $count;
    }
}
