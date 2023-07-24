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
    /**
     * @var int
     */
    protected $linesValid;

    /**
     * @var int
     */
    protected $linesCovered;

    /**
     * @var int
     */
    protected $branchesValid;

    /**
     * @var int
     */
    protected $branchesCovered;

    public function __construct(int $linesValid, int $linesCovered, int $branchesValid, int $branchesCovered)
    {
        $this->linesValid      = $linesValid;
        $this->linesCovered    = $linesCovered;
        $this->branchesValid   = $branchesValid;
        $this->branchesCovered = $branchesCovered;
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
