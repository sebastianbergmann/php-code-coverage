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

use SebastianBergmann\CodeCoverage\Data\ProcessedFunctionCoverageData;

/**
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for phpunit/php-code-coverage
 */
final readonly class FileCoverageData
{
    /**
     * @var array<positive-int, null|list<non-empty-string>>
     */
    public array $lineCoverage;

    /**
     * @var array<non-empty-string, ProcessedFunctionCoverageData>
     */
    public array $functionCoverage;

    /**
     * @param array<positive-int, null|list<non-empty-string>>       $lineCoverage
     * @param array<non-empty-string, ProcessedFunctionCoverageData> $functionCoverage
     */
    public function __construct(array $lineCoverage, array $functionCoverage)
    {
        $this->lineCoverage     = $lineCoverage;
        $this->functionCoverage = $functionCoverage;
    }
}
