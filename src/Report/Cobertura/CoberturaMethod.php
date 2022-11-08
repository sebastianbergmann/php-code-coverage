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

use DOMDocument;
use DOMElement;

class CoberturaMethod extends CoberturaElement
{
    /** @var CoberturaLine[] */
    private array $lines = [];

    public static function create(string $name, array $methodData, array $lineCoverageData): ?self
    {
        if ($methodData['executableLines'] === 0) {
            return null;
        }

        $method = new self($name, $methodData['signature'], $methodData['executableLines'], $methodData['executedLines'], $methodData['executableBranches'], $methodData['executedBranches']);

        /** @var int $lineNumber */
        foreach (range($methodData['startLine'], $methodData['endLine']) as $lineNumber) {
            $line = CoberturaLine::create($lineNumber, $lineCoverageData);

            if (null !== $line) {
                $method->lines[] = $line;
            }
        }

        return $method;
    }

    private function __construct(private string $name, private string $signature, int $linesValid, int $linesCovered, int $branchesValid, int $branchesCovered)
    {
        parent::__construct($linesValid, $linesCovered, $branchesValid, $branchesCovered);
    }

    public function wrap(DOMDocument $document): DOMElement
    {
        $methodElement = $document->createElement('method');

        $methodElement->setAttribute('name', $this->name);
        $methodElement->setAttribute('signature', $this->signature);
        $methodElement->setAttribute('line-rate', (string) $this->lineRate());
        $methodElement->setAttribute('branch-rate', (string) $this->branchRate());

        $linesElement = $document->createElement('lines');

        foreach ($this->lines as $line) {
            $linesElement->appendChild($line->wrap($document));
        }

        $methodElement->appendChild($linesElement);

        return $methodElement;
    }

    public function getLines(): array
    {
        return $this->lines;
    }
}
