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

final class ProcessedTraitType
{
    public function __construct(
        public readonly string $traitName,
        public readonly string $namespace,
        /**
         * @var array<string, ProcessedMethodType>
         */
        public array $methods,
        public readonly int $startLine,
        public int $executableLines,
        public int $executedLines,
        public int $executableBranches,
        public int $executedBranches,
        public int $executablePaths,
        public int $executedPaths,
        public int $ccn,
        public float|int $coverage,
        public int|string $crap,
        public readonly string $link,
    ) {
    }
}
