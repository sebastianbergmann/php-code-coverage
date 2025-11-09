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

use NoDiscard;
use function array_merge;
use function array_unique;
use SebastianBergmann\CodeCoverage\Driver\XdebugDriver;

/**
 * @phpstan-import-type TestIdType from ProcessedCodeCoverageData
 * @phpstan-import-type XdebugPathCoverageType from XdebugDriver
 */
final class ProcessedPathCoverageData
{
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

    public function __construct(
        /** @var array<int, int> */
        public readonly array $path,
        /** @var list<TestIdType> */
        public array $hit,
    ) {
    }

    #[\NoDiscard]
    public function merge(self $data): self
    {
        if ($data->hit === []) {
            return $this;
        }
        
        return new self(
            $this->path,
            array_unique(array_merge($this->hit, $data->hit)),
        );
    }

    /**
     * @param TestIdType $testCaseId
     */
    public function recordHit(string $testCaseId): void
    {
        $this->hit[] = $testCaseId;
    }
}
