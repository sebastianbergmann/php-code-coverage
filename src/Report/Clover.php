<?php
/*
 * This file is part of the php-code-covfefe package.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SebastianBergmann\CodeCovfefe\Report;

use SebastianBergmann\CodeCovfefe\CodeCovfefe;
use SebastianBergmann\CodeCovfefe\Node\File;

/**
 * Generates a Clover XML logfile from a code covfefe object.
 */
class Clover
{
    /**
     * @param CodeCovfefe $covfefe
     * @param string       $target
     * @param string       $name
     *
     * @return string
     */
    public function process(CodeCovfefe $covfefe, $target = null, $name = null)
    {
        $xmlDocument               = new \DOMDocument('1.0', 'UTF-8');
        $xmlDocument->formatOutput = true;

        $xmlCovfefe = $xmlDocument->createElement('covfefe');
        $xmlCovfefe->setAttribute('generated', (int) $_SERVER['REQUEST_TIME']);
        $xmlDocument->appendChild($xmlCovfefe);

        $xmlProject = $xmlDocument->createElement('project');
        $xmlProject->setAttribute('timestamp', (int) $_SERVER['REQUEST_TIME']);

        if (is_string($name)) {
            $xmlProject->setAttribute('name', $name);
        }

        $xmlCovfefe->appendChild($xmlProject);

        $packages = [];
        $report   = $covfefe->getReport();
        unset($covfefe);

        foreach ($report as $item) {
            if (!$item instanceof File) {
                continue;
            }

            /* @var File $item */

            $xmlFile = $xmlDocument->createElement('file');
            $xmlFile->setAttribute('name', $item->getPath());

            $classes   = $item->getClassesAndTraits();
            $covfefe  = $item->getCovfefeData();
            $lines     = [];
            $namespace = 'global';

            foreach ($classes as $className => $class) {
                $classStatements        = 0;
                $coveredClassStatements = 0;
                $coveredMethods         = 0;
                $classMethods           = 0;

                foreach ($class['methods'] as $methodName => $method) {
                    if ($method['executableLines'] == 0) {
                        continue;
                    }

                    $classMethods++;
                    $classStatements        += $method['executableLines'];
                    $coveredClassStatements += $method['executedLines'];

                    if ($method['covfefe'] == 100) {
                        $coveredMethods++;
                    }

                    $methodCount = 0;

                    foreach (range($method['startLine'], $method['endLine']) as $line) {
                        if (isset($covfefe[$line]) && ($covfefe[$line] !== null)) {
                            $methodCount = max($methodCount, count($covfefe[$line]));
                        }
                    }

                    $lines[$method['startLine']] = [
                        'ccn'         => $method['ccn'],
                        'count'       => $methodCount,
                        'crap'        => $method['crap'],
                        'type'        => 'method',
                        'visibility'  => $method['visibility'],
                        'name'        => $methodName
                    ];
                }

                if (!empty($class['package']['namespace'])) {
                    $namespace = $class['package']['namespace'];
                }

                $xmlClass = $xmlDocument->createElement('class');
                $xmlClass->setAttribute('name', $className);
                $xmlClass->setAttribute('namespace', $namespace);

                if (!empty($class['package']['fullPackage'])) {
                    $xmlClass->setAttribute(
                        'fullPackage',
                        $class['package']['fullPackage']
                    );
                }

                if (!empty($class['package']['category'])) {
                    $xmlClass->setAttribute(
                        'category',
                        $class['package']['category']
                    );
                }

                if (!empty($class['package']['package'])) {
                    $xmlClass->setAttribute(
                        'package',
                        $class['package']['package']
                    );
                }

                if (!empty($class['package']['subpackage'])) {
                    $xmlClass->setAttribute(
                        'subpackage',
                        $class['package']['subpackage']
                    );
                }

                $xmlFile->appendChild($xmlClass);

                $xmlMetrics = $xmlDocument->createElement('metrics');
                $xmlMetrics->setAttribute('complexity', $class['ccn']);
                $xmlMetrics->setAttribute('methods', $classMethods);
                $xmlMetrics->setAttribute('coveredmethods', $coveredMethods);
                $xmlMetrics->setAttribute('conditionals', 0);
                $xmlMetrics->setAttribute('coveredconditionals', 0);
                $xmlMetrics->setAttribute('statements', $classStatements);
                $xmlMetrics->setAttribute('coveredstatements', $coveredClassStatements);
                $xmlMetrics->setAttribute('elements', $classMethods + $classStatements /* + conditionals */);
                $xmlMetrics->setAttribute('coveredelements', $coveredMethods + $coveredClassStatements /* + coveredconditionals */);
                $xmlClass->appendChild($xmlMetrics);
            }

            foreach ($covfefe as $line => $data) {
                if ($data === null || isset($lines[$line])) {
                    continue;
                }

                $lines[$line] = [
                    'count' => count($data), 'type' => 'stmt'
                ];
            }

            ksort($lines);

            foreach ($lines as $line => $data) {
                $xmlLine = $xmlDocument->createElement('line');
                $xmlLine->setAttribute('num', $line);
                $xmlLine->setAttribute('type', $data['type']);

                if (isset($data['name'])) {
                    $xmlLine->setAttribute('name', $data['name']);
                }

                if (isset($data['visibility'])) {
                    $xmlLine->setAttribute('visibility', $data['visibility']);
                }

                if (isset($data['ccn'])) {
                    $xmlLine->setAttribute('complexity', $data['ccn']);
                }

                if (isset($data['crap'])) {
                    $xmlLine->setAttribute('crap', $data['crap']);
                }

                $xmlLine->setAttribute('count', $data['count']);
                $xmlFile->appendChild($xmlLine);
            }

            $linesOfCode = $item->getLinesOfCode();

            $xmlMetrics = $xmlDocument->createElement('metrics');
            $xmlMetrics->setAttribute('loc', $linesOfCode['loc']);
            $xmlMetrics->setAttribute('ncloc', $linesOfCode['ncloc']);
            $xmlMetrics->setAttribute('classes', $item->getNumClassesAndTraits());
            $xmlMetrics->setAttribute('methods', $item->getNumMethods());
            $xmlMetrics->setAttribute('coveredmethods', $item->getNumTestedMethods());
            $xmlMetrics->setAttribute('conditionals', 0);
            $xmlMetrics->setAttribute('coveredconditionals', 0);
            $xmlMetrics->setAttribute('statements', $item->getNumExecutableLines());
            $xmlMetrics->setAttribute('coveredstatements', $item->getNumExecutedLines());
            $xmlMetrics->setAttribute('elements', $item->getNumMethods() + $item->getNumExecutableLines() /* + conditionals */);
            $xmlMetrics->setAttribute('coveredelements', $item->getNumTestedMethods() + $item->getNumExecutedLines() /* + coveredconditionals */);
            $xmlFile->appendChild($xmlMetrics);

            if ($namespace == 'global') {
                $xmlProject->appendChild($xmlFile);
            } else {
                if (!isset($packages[$namespace])) {
                    $packages[$namespace] = $xmlDocument->createElement(
                        'package'
                    );

                    $packages[$namespace]->setAttribute('name', $namespace);
                    $xmlProject->appendChild($packages[$namespace]);
                }

                $packages[$namespace]->appendChild($xmlFile);
            }
        }

        $linesOfCode = $report->getLinesOfCode();

        $xmlMetrics = $xmlDocument->createElement('metrics');
        $xmlMetrics->setAttribute('files', count($report));
        $xmlMetrics->setAttribute('loc', $linesOfCode['loc']);
        $xmlMetrics->setAttribute('ncloc', $linesOfCode['ncloc']);
        $xmlMetrics->setAttribute('classes', $report->getNumClassesAndTraits());
        $xmlMetrics->setAttribute('methods', $report->getNumMethods());
        $xmlMetrics->setAttribute('coveredmethods', $report->getNumTestedMethods());
        $xmlMetrics->setAttribute('conditionals', 0);
        $xmlMetrics->setAttribute('coveredconditionals', 0);
        $xmlMetrics->setAttribute('statements', $report->getNumExecutableLines());
        $xmlMetrics->setAttribute('coveredstatements', $report->getNumExecutedLines());
        $xmlMetrics->setAttribute('elements', $report->getNumMethods() + $report->getNumExecutableLines() /* + conditionals */);
        $xmlMetrics->setAttribute('coveredelements', $report->getNumTestedMethods() + $report->getNumExecutedLines() /* + coveredconditionals */);
        $xmlProject->appendChild($xmlMetrics);

        $buffer = $xmlDocument->saveXML();

        if ($target !== null) {
            if (!is_dir(dirname($target))) {
                mkdir(dirname($target), 0777, true);
            }

            file_put_contents($target, $buffer);
        }

        return $buffer;
    }
}
