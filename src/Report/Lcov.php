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

use function dirname;
use function file_put_contents;
use function ksort;
use function range;
use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Directory;
use SebastianBergmann\CodeCoverage\Driver\WriteOperationFailedException;
use SebastianBergmann\CodeCoverage\Node\File;

/**
 * Ref: http://ltp.sourceforge.net/coverage/lcov/geninfo.1.php.
 */
final class Lcov
{
    /**
     * @throws WriteOperationFailedException
     */
    public function process(CodeCoverage $coverage, ?string $target = null, ?string $name = null): string
    {
        $lcovLines = [];
        $report    = $coverage->getReport();

        /** @var File $file */
        foreach ($report as $file) {
            $tests = $this->getCoverageByTestCase($file);

            foreach ($tests as $name => $test) {
                $lcovLines[] = 'TN:' . $this->sanitizeTestName($name);
                $lcovLines[] = 'SF:' . $file->pathAsString();

                foreach ($test['lineNumbers'] as $lineNumber) {
                    $hit         = in_array($lineNumber, $test['lineNumbersHit'], true) ? 1 : 0;
                    $lcovLines[] = "DA:{$lineNumber},{$hit}";
                }
                $lcovLines[] = 'LF:' . $file->numberOfExecutableLines();
                $lcovLines[] = 'LH:' . $file->numberOfExecutedLines();

                foreach ($test['functionLineNumbers'] as $functionName => $lineNumber) {
                    $hit         = in_array($lineNumber, $test['functionLineNumbersHit'], true) ? 1 : 0;
                    $lcovLines[] = "FN:{$lineNumber},{$functionName}";
                    $lcovLines[] = "FNDA:{$hit},{$functionName}";
                }
                $lcovLines[] = 'FNF:' . $file->numberOfMethods();
                $lcovLines[] = 'FNH:' . $file->numberOfTestedMethods();

                foreach ($test['branchLineNumbers'] as $data) {
                    [$branchId, $lineNumber, $branchHit] = $data;
                    $lcovLines[]                         = "BRDA:{$lineNumber},0,{$branchId},{$branchHit}";
                }
                $lcovLines[] = 'BRF:' . $file->numberOfExecutableBranches();
                $lcovLines[] = 'BRH:' . $file->numberOfExecutedBranches();

                $lcovLines[] = 'end_of_record';
            }
        }

        $buffer = implode("\n", $lcovLines) . "\n";

        if ($target !== null) {
            $this->writeFile($target, $buffer);
        }

        return $buffer;
    }

    private function sanitizeTestName(string $testName): string
    {
        $testName = explode('::', $testName);

        if (isset($testName[1])) {
            return $testName[1];
        }

        return $testName[0];
    }

    private function getCoverageByTestCase(File $file): array
    {
        $testSpecificData = [];
        $tests            = array_keys($file->testData());

        foreach ($tests as $test) {
            $testSpecificData[$test] = [
                'lineNumbers'            => [],
                'lineNumbersHit'         => [],
                'functionLineNumbers'    => [],
                'functionLineNumbersHit' => [],
                'branchLineNumbers'      => [],
            ];

            foreach ($file->lineCoverageData() as $lineNumber => $data) {
                if ($data === null || in_array($lineNumber, $testSpecificData[$test]['lineNumbers'], true)) {
                    continue;
                }

                $testSpecificData[$test]['lineNumbers'][] = $lineNumber;

                if (!in_array($test, $data, true)) {
                    continue;
                }

                $testSpecificData[$test]['lineNumbersHit'][] = $lineNumber;
            }

            foreach ($file->classesAndTraits() as $class) {
                foreach ($class['methods'] as $methodName => $method) {
                    if (0 == $method['executableLines']) {
                        continue;
                    }

                    $testSpecificData[$test]['functionLineNumbers'][$methodName] = $method['startLine'];

                    if (0 < $method['executedLines'] && $method['coverage'] == 100) {
                        $testSpecificData[$test]['functionLineNumbersHit'][$methodName] = $method['startLine'];
                    }
                }
            }

            foreach ($file->functionCoverageData() as $data) {
                foreach ($data['branches'] as $branchId => $branch) {
                    $branchHit = '-';

                    foreach (range($branch['line_start'], $branch['line_end']) as $lineNumber) {
                        if (in_array($lineNumber, $testSpecificData[$test]['lineNumbersHit'], true) &&
                            in_array($test, $branch['hit'], true)) {
                            $branchHit = '1';

                            break;
                        }
                    }

                    $testSpecificData[$test]['branchLineNumbers'][] = [$branchId, $branch['line_start'], $branchHit];
                }
            }
        }

        ksort($testSpecificData);

        return $testSpecificData;
    }

    private function writeFile(string $target, string $buffer): void
    {
        Directory::create(dirname($target));

        if (@file_put_contents($target, $buffer) === false) {
            throw new WriteOperationFailedException($target);
        }
    }
}
