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
use DOMImplementation;
use SebastianBergmann\CodeCoverage\Node\AbstractNode;
use SebastianBergmann\CodeCoverage\Node\Directory;
use SebastianBergmann\CodeCoverage\Node\File;

class CoberturaCoverage extends CoberturaElement
{
    private const FUNCTIONS_PACKAGE = '_functions';

    /** @var string[] */
    private array $sources = [];

    /** @var array<string, CoberturaPackage> */
    private array $packages = [];

    public static function create(Directory $report): self
    {
        $coverage = new self(
            time(),
            $report->numberOfExecutableLines(),
            $report->numberOfExecutedLines(),
            $report->numberOfExecutableBranches(),
            $report->numberOfExecutedBranches(),
            $report->numberOfFunctionsAndMethods(),
            $report->numberOfTestedFunctionsAndMethods(),
            $report->numberOfClassesAndTraits(),
            $report->numberOfTestedClassesAndTraits(),
        );

        foreach ($report as $item) {
            if (!$item instanceof File) {
                continue;
            }

            $coverage->processFile($item);
        }

        return $coverage;
    }

    private function __construct(
        private int $timestamp,
        int $linesValid,
        int $linesCovered,
        int $branchesValid,
        int $branchesCovered,
        private int $methodsValid,
        private int $methodsCovered,
        private int $classesValid,
        private int $classesCovered
    ) {
        parent::__construct($linesValid, $linesCovered, $branchesValid, $branchesCovered);
    }

    public function generateDocument(): DOMDocument
    {
        $implementation = new DOMImplementation;

        $documentType = $implementation->createDocumentType(
            'coverage',
            '',
            'http://cobertura.sourceforge.net/xml/coverage-04.dtd'
        );

        $document               = $implementation->createDocument('', '', $documentType);
        $document->xmlVersion   = '1.0';
        $document->encoding     = 'UTF-8';
        $document->formatOutput = true;

        $methodRate = $this->methodsValid === 0 ? 0 : $this->methodsCovered / $this->methodsValid;
        $classRate  = $this->classesValid === 0 ? 0 : $this->classesCovered / $this->classesValid;

        $coverageElement = $document->createElement('coverage');
        $coverageElement->setAttribute('line-rate', (string) $this->lineRate());
        $coverageElement->setAttribute('branch-rate', (string) $this->branchRate());
        $coverageElement->setAttribute('method-rate', (string) $methodRate);
        $coverageElement->setAttribute('class-rate', (string) $classRate);
        $coverageElement->setAttribute('lines-covered', (string) $this->linesCovered);
        $coverageElement->setAttribute('lines-valid', (string) $this->linesValid);
        $coverageElement->setAttribute('branches-covered', (string) $this->branchesCovered);
        $coverageElement->setAttribute('branches-valid', (string) $this->branchesValid);
        $coverageElement->setAttribute('methods-covered', (string) $this->methodsCovered);
        $coverageElement->setAttribute('methods-valid', (string) $this->methodsValid);
        $coverageElement->setAttribute('classes-covered', (string) $this->classesCovered);
        $coverageElement->setAttribute('classes-valid', (string) $this->classesValid);
        $coverageElement->setAttribute('complexity', (string) $this->complexity());
        $coverageElement->setAttribute('version', '0.4');
        $coverageElement->setAttribute('timestamp', (string) $this->timestamp);

        $coverageElement->appendChild($this->wrapSources($document));

        $packagesElement = $document->createElement('packages');

        foreach ($this->packages as $package) {
            $packagesElement->appendChild($package->wrap($document));
        }

        $coverageElement->appendChild($packagesElement);

        $document->appendChild($coverageElement);

        return $document;
    }

    private function processFile(File $file): void
    {
        $this->addSource($this->relativePath($this->fileRoot($file)->pathAsString()));

        $lineCoverageData = $file->lineCoverageData();

        foreach ($file->classesAndTraits() as $className => $classData) {
            $class = CoberturaClass::create(
                $className,
                $this->relativePath($file->pathAsString()),
                $classData,
                $lineCoverageData
            );

            $packageName = CoberturaPackage::packageName($class->getName());

            if (!isset($this->packages[$packageName])) {
                $this->packages[$packageName] = new CoberturaPackage($packageName);
            }

            $this->packages[$packageName]->addClass($class);
        }

        $this->processFunctions($file);
    }

    private function processFunctions(File $file): void
    {
        $lineCoverageData = $file->lineCoverageData();

        $functions       = [];
        $classComplexity = 0;

        foreach ($file->functions() as $functionName => $functionData) {
            $method = CoberturaMethod::create($functionName, $functionData, $lineCoverageData);

            if (null !== $method) {
                $functions[$functionName] = $method;
                $classComplexity += $functionData['ccn'];
            }
        }

        if (count($functions) > 0) {
            $classCoverageData = array_reduce($functions, static function (array $data, CoberturaMethod $function)
            {
                $data['linesValid'] += $function->getLinesValid();
                $data['linesCovered'] += $function->getLinesCovered();
                $data['branchesValid'] += $function->getBranchesValid();
                $data['branchesCovered'] += $function->getBranchesCovered();

                return $data;
            }, ['linesValid' => 0, 'linesCovered' => 0, 'branchesValid' => 0, 'branchesCovered' => 0]);

            $relativeFilePath = $this->relativePath($file->pathAsString());

            $class = CoberturaClass::createForFunctions(
                self::FUNCTIONS_PACKAGE . '\\' . basename($relativeFilePath),
                $relativeFilePath,
                $classCoverageData['linesValid'],
                $classCoverageData['linesCovered'],
                $classCoverageData['branchesValid'],
                $classCoverageData['branchesCovered'],
                $classComplexity,
                $functions
            );

            if (!isset($this->packages[self::FUNCTIONS_PACKAGE])) {
                $this->packages[self::FUNCTIONS_PACKAGE] = new CoberturaPackage(self::FUNCTIONS_PACKAGE);
            }

            $this->packages[self::FUNCTIONS_PACKAGE]->addClass($class);
        }
    }

    private function addSource(string $source): void
    {
        if (!in_array($source, $this->sources, true)) {
            $this->sources[] = $source;
        }
    }

    private function fileRoot(File $file): AbstractNode
    {
        $root = $file;

        while (true) {
            if ($root->parent() === null) {
                return $root;
            }

            /** @var AbstractNode $root */
            $root = $root->parent();
        }
    }

    private function relativePath(string $path): string
    {
        return str_replace(getcwd() . DIRECTORY_SEPARATOR, '', $path);
    }

    private function wrapSources(DOMDocument $document): DOMElement
    {
        $sourcesElement = $document->createElement('sources');

        foreach ($this->sources as $source) {
            $sourcesElement->appendChild($document->createElement('source', $source));
        }

        return $sourcesElement;
    }

    private function complexity(): float
    {
        return array_reduce(
            $this->packages,
            static fn (float $complexity, CoberturaPackage $package) => $complexity + $package->complexity(),
            0
        );
    }
}
