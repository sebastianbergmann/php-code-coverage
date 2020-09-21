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

use function count;
use function dirname;
use function file_put_contents;
use function max;
use function range;
use function time;
use DOMImplementation;
use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Directory;
use SebastianBergmann\CodeCoverage\Driver\WriteOperationFailedException;
use SebastianBergmann\CodeCoverage\Node\File;

/**
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 */
final class Cobertura
{
    /**
     * @throws WriteOperationFailedException
     */
    public function process(CodeCoverage $coverage, ?string $target = null, ?string $name = null): string
    {
        $time = (string) time();

        $report = $coverage->getReport();

        $impl = new DOMImplementation();
        $dtd  = $impl->createDocumentType(
            'coverage',
            '',
            'http://cobertura.sourceforge.net/xml/coverage-04.dtd'
        );

        $xmlDocument               = $impl->createDocument('', '', $dtd);
        $xmlDocument->xmlVersion   = '1.0';
        $xmlDocument->encoding     = 'UTF-8';
        $xmlDocument->formatOutput = true;

        $xmlCoverage = $xmlDocument->createElement('coverage');

        // Line rate.
        $linesValid   = $report->numberOfExecutedLines();
        $linesCovered = $report->numberOfExecutableLines();
        $lineRate     = $linesValid === 0 ? 0 : ($linesCovered / $linesValid);
        $xmlCoverage->setAttribute('line-rate', (string) $lineRate);

        // Branch rate.
        $branchesValid   = $report->numberOfExecutedBranches();
        $branchesCovered = $report->numberOfExecutableBranches();
        $branchRate      = $branchesValid === 0 ? 0 : ($branchesCovered / $branchesValid);
        $xmlCoverage->setAttribute('branch-rate', (string) $branchRate);

        $xmlCoverage->setAttribute('lines-covered', (string) $report->numberOfExecutedLines());
        $xmlCoverage->setAttribute('lines-valid', (string) $report->numberOfExecutableLines());
        $xmlCoverage->setAttribute('branches-covered', (string) $report->numberOfExecutedBranches());
        $xmlCoverage->setAttribute('branches-valid', (string) $report->numberOfExecutableBranches());
        $xmlCoverage->setAttribute('complexity', '');
        $xmlCoverage->setAttribute('version', '0.4');
        $xmlCoverage->setAttribute('timestamp', $time);
        $xmlDocument->appendChild($xmlCoverage);

        $xmlSources = $xmlDocument->createElement('sources');
        $xmlCoverage->appendChild($xmlSources);

        $xmlSource = $xmlDocument->createElement('source', $report->pathAsString());
        $xmlSources->appendChild($xmlSource);

        $xmlPackages = $xmlDocument->createElement('packages');
        $xmlCoverage->appendChild($xmlPackages);

        $complexity = 0;

        foreach ($report as $item) {
            if (!$item instanceof File) {
                continue;
            }

            $packageComplexity = 0;

            $xmlPackage = $xmlDocument->createElement('package');

            $packageName = '';

            if ($name !== null) {
                $packageName = $name;
            }
            $xmlPackage->setAttribute('name', $packageName);

            $linesValid   = $item->numberOfExecutableLines();
            $linesCovered = $item->numberOfExecutedLines();
            $lineRate     = $linesValid === 0 ? 0 : ($linesCovered / $linesValid);
            $xmlPackage->setAttribute('line-rate', (string) $lineRate);

            $branchesValid   = $item->numberOfExecutableBranches();
            $branchesCovered = $item->numberOfExecutedBranches();
            $branchRate      = $branchesValid === 0 ? 0 : ($branchesCovered / $branchesValid);
            $xmlPackage->setAttribute('branch-rate', (string) $branchRate);

            $xmlPackage->setAttribute('complexity', '');
            $xmlPackages->appendChild($xmlPackage);

            $xmlClasses = $xmlDocument->createElement('classes');
            $xmlPackage->appendChild($xmlClasses);

            $classes      = $item->classesAndTraits();
            $coverageData = $item->lineCoverageData();

            foreach ($classes as $className => $class) {
                $complexity += $class['ccn'];
                $packageComplexity += $class['ccn'];

                if (!empty($class['package']['namespace'])) {
                    $className = $class['package']['namespace'] . '\\' . $className;
                }

                $linesValid   = $class['executableLines'];
                $linesCovered = $class['executedLines'];
                $lineRate     = $linesValid === 0 ? 0 : ($linesCovered / $linesValid);

                $branchesValid   = $class['executableBranches'];
                $branchesCovered = $class['executedBranches'];
                $branchRate      = $branchesValid === 0 ? 0 : ($branchesCovered / $branchesValid);

                $xmlClass = $xmlDocument->createElement('class');
                $xmlClass->setAttribute('name', $className);
                $xmlClass->setAttribute('filename', str_replace($report->pathAsString() . '/', '', $item->pathAsString()));
                $xmlClass->setAttribute('line-rate', (string) $lineRate);
                $xmlClass->setAttribute('branch-rate', (string) $branchRate);
                $xmlClass->setAttribute('complexity', (string) $class['ccn']);
                $xmlClasses->appendChild($xmlClass);

                $xmlMethods = $xmlDocument->createElement('methods');
                $xmlClass->appendChild($xmlMethods);

                $xmlClassLines = $xmlDocument->createElement('lines');
                $xmlClass->appendChild($xmlClassLines);

                foreach ($class['methods'] as $methodName => $method) {
                    if ($method['executableLines'] == 0) {
                        continue;
                    }

                    $methodCount = 0;

                    foreach (range($method['startLine'], $method['endLine']) as $line) {
                        if (isset($coverageData[$line]) && $coverageData[$line] !== null) {
                            $methodCount = max($methodCount, count($coverageData[$line]));

                            $xmlClassLine = $xmlDocument->createElement('line');
                            $xmlClassLine->setAttribute('number', (string) $line);
                            $xmlClassLine->setAttribute('hits', (string) count($coverageData[$line]));
                            $xmlClassLines->appendChild($xmlClassLine);
                        }
                    }

                    $linesValid   = $method['executableLines'];
                    $linesCovered = $method['executedLines'];
                    $lineRate     = $linesValid === 0 ? 0 : ($linesCovered / $linesValid);

                    $branchesValid   = $method['executableBranches'];
                    $branchesCovered = $method['executedBranches'];
                    $branchRate      = $branchesValid === 0 ? 0 : ($branchesCovered / $branchesValid);

                    $xmlMethod = $xmlDocument->createElement('method');
                    $xmlMethod->setAttribute('name', $methodName);
                    $xmlMethod->setAttribute('signature', $method['signature']);
                    $xmlMethod->setAttribute('line-rate', (string) $lineRate);
                    $xmlMethod->setAttribute('branch-rate', (string) $branchRate);
                    $xmlMethod->setAttribute('complexity', (string) $method['ccn']);

                    $xmlLines = $xmlDocument->createElement('lines');
                    $xmlMethod->appendChild($xmlLines);

                    $xmlLine = $xmlDocument->createElement('line');
                    $xmlLine->setAttribute('number', (string) $method['startLine']);
                    $xmlLine->setAttribute('hits', (string) $methodCount);
                    $xmlLines->appendChild($xmlLine);

                    $xmlMethods->appendChild($xmlMethod);
                }
            }

            $xmlPackage->setAttribute('complexity', (string) $packageComplexity);
        }

        $xmlCoverage->setAttribute('complexity', (string) $complexity);

        $buffer = $xmlDocument->saveXML();

        if ($target !== null) {
            Directory::create(dirname($target));

            if (@file_put_contents($target, $buffer) === false) {
                throw new WriteOperationFailedException($target);
            }
        }

        return $buffer;
    }
}
