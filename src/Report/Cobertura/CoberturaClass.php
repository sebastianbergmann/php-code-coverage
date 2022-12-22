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

use function array_merge;
use function range;
use DOMDocument;
use DOMElement;

class CoberturaClass extends CoberturaElement
{
    /** @var CoberturaMethod[] */
    private $methods = [];

    /** @var CoberturaLine[] */
    private $lines = [];

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $filename;

    /**
     * @var float
     */
    private $complexity;

    public static function create(string $className, string $relativeFilePath, array $classData, array $lineCoverageData): self
    {
        if (!empty($classData['package']['namespace'])) {
            $className = $classData['package']['namespace'] . '\\' . $className;
        }

        $class = new self(
            $className,
            $relativeFilePath,
            $classData['executableLines'],
            $classData['executedLines'],
            $classData['executableBranches'],
            $classData['executedBranches'],
            $classData['ccn']
        );

        $endLine = $classData['startLine'];

        foreach ($classData['methods'] as $methodName => $methodData) {
            $method = CoberturaMethod::create($methodName, $methodData, $lineCoverageData);

            if (null !== $method) {
                $class->methods[] = $method;
            }

            if ($methodData['endLine'] > $endLine) {
                $endLine = $methodData['endLine'];
            }
        }

        /** @var int $lineNumber */
        foreach (range($classData['startLine'], $endLine) as $lineNumber) {
            $line = CoberturaLine::create($lineNumber, $lineCoverageData);

            if (null !== $line) {
                $class->lines[] = $line;
            }
        }

        return $class;
    }

    public static function createForFunctions(
        string $className,
        string $relativeFilePath,
        int $linesValid,
        int $linesCovered,
        int $branchesValid,
        int $branchesCovered,
        float $complexity,
        array $functions
    ): self {
        $class = new self(
            $className,
            $relativeFilePath,
            $linesValid,
            $linesCovered,
            $branchesValid,
            $branchesCovered,
            $complexity
        );

        $class->methods = $functions;

        foreach ($class->methods as $method) {
            $class->lines = array_merge($class->lines, $method->getLines());
        }

        return $class;
    }

    private function __construct(
        string $name,
        string $filename,
        int $linesValid,
        int $linesCovered,
        int $branchesValid,
        int $branchesCovered,
        float $complexity
    ) {
        $this->name       = $name;
        $this->filename   = $filename;
        $this->complexity = $complexity;
        parent::__construct($linesValid, $linesCovered, $branchesValid, $branchesCovered);
    }

    public function wrap(DOMDocument $document): DOMElement
    {
        $classElement = $document->createElement('class');

        $classElement->setAttribute('name', $this->name);
        $classElement->setAttribute('filename', $this->filename);
        $classElement->setAttribute('line-rate', (string) $this->lineRate());
        $classElement->setAttribute('branch-rate', (string) $this->branchRate());
        $classElement->setAttribute('complexity', (string) $this->complexity);

        $methodsElement = $document->createElement('methods');

        foreach ($this->methods as $method) {
            $methodsElement->appendChild($method->wrap($document));
        }

        $classElement->appendChild($methodsElement);

        $linesElement = $document->createElement('lines');

        foreach ($this->lines as $line) {
            $linesElement->appendChild($line->wrap($document));
        }

        $classElement->appendChild($linesElement);

        return $classElement;
    }

    public function getComplexity(): float
    {
        return $this->complexity;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
