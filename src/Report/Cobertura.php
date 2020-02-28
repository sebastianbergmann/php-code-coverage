<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\Report;

use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Node\Directory;
use SebastianBergmann\CodeCoverage\Node\File;

final class Cobertura
{
    /**
     * Process coverage and generate a Cobertura report.
     */
    public function process(CodeCoverage $coverage, string $packageName): string
    {
        $document = $this->createBaseDocument();

        $elClasses = $document->createElement('classes');

        $coverageFiles = $coverage->getReport()->getFiles();

        $validLineClasses   = 0;
        $coveredLineClasses = 0;

        /** @var File $file */
        foreach ($coverageFiles as $file) {
            if (!$file instanceof File) {
                continue;
            }

            $coverageData    = $file->getCoverageData();
            $coverageClasses = $file->getClassesAndTraits();
            $currentFileName = $file->getPath();

            foreach ($coverageClasses as $class) {
                if (!empty($class['package']['namespace'])) {
                    $namespace = $class['package']['namespace'];
                    $className = $namespace . '\\' . $class['className'];
                } else {
                    $className = $class['className'];
                }

                $linesValid   = $class['executableLines'];
                $linesCovered = $class['executedLines'];

                $validLineClasses += $linesValid;
                $coveredLineClasses += $linesCovered;

                $classLineRate = $linesValid === 0 ? 0 : ($linesCovered / $linesValid);
                $methods       = $document->createElement('methods');
                $classLines    = $document->createElement('lines');
                $coverageLines = $coverageData;

                $elClass = $document->createElement('class');
                $elClass->setAttribute('name', $className);
                $elClass->setAttribute('complexity', (string) $class['ccn']);
                $elClass->setAttribute('branch-rate', '0');
                $elClass->setAttribute('line-rate', (string) $classLineRate);
                $elClass->setAttribute('filename', $currentFileName);

                if ($linesValid !== 0) {
                    $classMethods = $class['methods'];
                } else {
                    $classMethods = []; // Nothing to cover, skip processing
                }

                foreach ($classMethods as $method) {
                    $methodLinesStart = $method['startLine'];
                    $methodLinesEnd   = $method['endLine'];

                    $methodLinesValid    = $method['executableLines'];
                    $methodLinesExecuted = $method['executedLines'];
                    $methodLines         = $document->createElement('lines');

                    foreach ($coverageLines as $lineNumber => $coveredBy) {
                        if ($lineNumber < $methodLinesStart || $lineNumber >= $methodLinesEnd) {
                            continue;
                        }

                        $lineHits = $coveredBy === null ? '0' : (string) \count($coveredBy);

                        $elLine = $document->createElement('line');
                        $elLine->setAttribute('number', (string) $lineNumber);
                        $elLine->setAttribute('hits', $lineHits);

                        $classLine = $elLine->cloneNode();

                        $methodLines->appendChild($elLine);
                        $classLines->appendChild($classLine);
                    }

                    $methodLineRate = 0;

                    if ($methodLinesValid !== 0) {
                        $methodLineRate = $methodLinesExecuted / $methodLinesValid;
                    }

                    $elMethod = $document->createElement('method');
                    $elMethod->setAttribute('name', $method['methodName']);
                    $elMethod->setAttribute('signature', $method['signature']);
                    $elMethod->setAttribute('complexity', (string) $method['ccn']);
                    $elMethod->setAttribute('branch-rate', '0');
                    $elMethod->setAttribute('line-rate', (string) $methodLineRate);

                    $elMethod->appendChild($methodLines);
                    $methods->appendChild($elMethod);
                }

                $elClass->appendChild($methods);

                $elClass->appendChild($classLines);

                $elClasses->appendChild($elClass);
            }
        }

        $report = $coverage->getReport();

        $coveragePath = $report->getPath();

        $combinedComplexity = $this->getCombinedComplexity($report);

        $totalLineRate      = $validLineClasses === 0 ? 0 : ($coveredLineClasses / $validLineClasses);

        $package = $document->createElement('package');
        $package->setAttribute('name', $packageName);
        $package->setAttribute('line-rate', (string) $totalLineRate);
        $package->setAttribute('branch-rate', '0');
        $package->setAttribute('complexity', (string) $combinedComplexity);

        $package->appendChild($elClasses);

        $elPackages = $document->createElement('packages');
        $elPackages->appendChild($package);

        $elSources = $document->createElement('sources');
        $source    = $document->createElement('source', $coveragePath);
        $elSources->appendChild($source);

        $elCoverage = $document->createElement('coverage');
        $elCoverage->setAttribute('lines-valid', (string) $validLineClasses);
        $elCoverage->setAttribute('lines-covered', (string) $coveredLineClasses);
        $elCoverage->setAttribute('line-rate', (string) $totalLineRate);

        // The following are in place to please DTD validation
        $elCoverage->setAttribute('branches-valid', '0');
        $elCoverage->setAttribute('branches-covered', '0');
        $elCoverage->setAttribute('branch-rate', '0');

        $elCoverage->setAttribute('timestamp', (string) $_SERVER['REQUEST_TIME']);
        $elCoverage->setAttribute('complexity', (string) $combinedComplexity);
        $elCoverage->setAttribute('version', '0.1');

        $elCoverage->appendChild($elSources);
        $elCoverage->appendChild($elPackages);

        $document->appendChild($elCoverage);

        if ($document->validate() === false) {
            throw new \RuntimeException('Could not generate Cobertura report, malformed report document generated');
        }

        $buffer = $document->saveXML();

        return $buffer;
    }

    /**
     * Create a base document for reporting.
     */
    private function createBaseDocument(): \DOMDocument
    {
        $impl = new \DOMImplementation();

        $dtd = $impl->createDocumentType(
            'coverage',
            '',
            'http://cobertura.sourceforge.net/xml/coverage-04.dtd'
        );

        $document                     = $impl->createDocument('', '', $dtd);
        $document->xmlVersion         = '1.0';
        $document->encoding           = 'UTF-8';
        $document->formatOutput       = true;
        $document->preserveWhiteSpace = false;

        return $document;
    }

    /**
     * Get combined code complexity for the coverage report.
     */
    private function getCombinedComplexity(Directory $report): int
    {
        $combined = 0;

        foreach ($report->getClasses() as $class) {
            $combined += (int) $class['ccn'];
        }

        return $combined;
    }
}
