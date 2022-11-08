<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\Report\Cobertura;

abstract class CoberturaElement
{
    public function __construct(protected int $linesValid, protected int $linesCovered, protected int $branchesValid, protected int $branchesCovered)
    {
    }

    public function getLinesValid(): int
    {
        return $this->linesValid;
    }

    public function getLinesCovered(): int
    {
        return $this->linesCovered;
    }

    public function getBranchesValid(): int
    {
        return $this->branchesValid;
    }

    public function getBranchesCovered(): int
    {
        return $this->branchesCovered;
    }

    protected function lineRate(): float
    {
        return $this->linesValid === 0 ? 0 : $this->linesCovered / $this->linesValid;
    }

    protected function branchRate(): float
    {
        return $this->branchesValid === 0 ? 0 : $this->branchesCovered / $this->branchesValid;
    }
}
