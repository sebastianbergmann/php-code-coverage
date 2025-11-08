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

use SebastianBergmann\CodeCoverage\Driver\XdebugDriver;

/**
 * @phpstan-import-type TestIdType from ProcessedCodeCoverageData
 * @phpstan-import-type XdebugPathCoverageType from XdebugDriver
 */
final readonly class ProcessedPathCoverageData {
    public function __construct(
        /** @var array<int, int> */
        public array $path,
        /** @var list<TestIdType> */
        public array $hit,
    ) {}

    /**
     * @param XdebugPathCoverageType $xdebugCoverageData
     */
    static public function fromXdebugCoverage(array $xdebugCoverageData): self
    {
        return new self(
            $xdebugCoverageData['path'],
            [],
        );
    }

    public function merge(self $data): self
    {
        return new self(
            $this->path,
            array_unique(array_merge($this->hit, $data->hit)),
        );
    }

    /**
     * @param TestIdType $testCaseId
     */
    public function recordHit(string $testCaseId): self
    {
        $hit = $this->hit;
        $hit[] = $testCaseId;

        return new self(
            $this->path,
            $hit
        );
    }

}
