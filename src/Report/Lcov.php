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
use function ksort;
use function max;
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

        foreach ($report as $file) {
            $lcovLines[] = 'TN:';
            $lcovLines[] = 'SF:' . $file->pathAsString();
            $lcovLines[] = 'FNF:' . $file->numberOfMethods();
            $lcovLines[] = 'FNH:' . $file->numberOfTestedMethods();

            $lines = $this->getLinesFromFile($file);

            foreach ($lines as $lineNumber => $data) {
                $numberExecution = array_key_exists('count', $data) ? $data['count'] : 0;

                if (isset($data['type']) && $data['type'] === 'method') {
                    $functionName = $data['name'] ?? "nameless-{$lineNumber}";

                    $lcovLines[] = "FN:{$lineNumber},{$functionName}";
                    $lcovLines[] = "FNDA:{$numberExecution},{$functionName}";

                    if (array_key_exists('branches', $data)) {
                        foreach ($data['branches'] as $branch) {
                            $lcovLines[] = "BRDA:{$lineNumber},{$branch['block']},{$branch['branch']},{$branch['count']}";
                        }
                    }
                } elseif (array_key_exists('branches', $data)) {
                    foreach ($data['branches'] as $branch) {
                        $lcovLines[] = "BRDA:{$lineNumber},{$branch['block']},{$branch['branch']},{$branch['count']}";
                        $lcovLines[] = "DA:{$lineNumber}," . ($branch['count'] === '-' ? '0' : $branch['count']);
                    }
                } else {
                    $lcovLines[] = "DA:{$lineNumber},{$numberExecution}";
                }
            }

            $lcovLines[] = 'BRF:' . $file->numberOfExecutableBranches();
            $lcovLines[] = 'BRH:' . $file->numberOfExecutedBranches();
            $lcovLines[] = 'LF:' . $file->numberOfExecutableLines();
            $lcovLines[] = 'LH:' . $file->numberOfExecutedLines();
            $lcovLines[] = 'end_of_record';
        }

        $buffer = implode("\n", $lcovLines) . "\n";

        if ($target !== null) {
            $this->writeFile($target, $buffer);
        }

        return $buffer;
    }

    private function writeFile(string $target, string $buffer): void
    {
        Directory::create(dirname($target));

        if (@file_put_contents($target, $buffer) === false) {
            throw new WriteOperationFailedException($target);
        }
    }

    private function getLinesFromFile(File $file): array
    {
        $lines        = [];
        $functionData = $file->functionCoverageData();
        $coverageData = $file->lineCoverageData();
        $classes      = $file->classesAndTraits();

        foreach ($classes as $class) {
            $className = $class['className'];

            foreach ($class['methods'] as $methodName => $method) {
                if ($method['executableLines'] == 0) {
                    continue;
                }

                $methodCount = 0;

                foreach (range($method['startLine'], $method['endLine']) as $line) {
                    if (isset($coverageData[$line]) && ($coverageData[$line] !== null)) {
                        $methodCount = max($methodCount, count($coverageData[$line]));
                    }
                }

                $lines[$method['startLine']] = [
                    'type'  => 'method',
                    'name'  => $methodName,
                    'count' => $methodCount,
                ];

                $functionDataIndex = $className . '->' . $method['methodName'];

                if (array_key_exists($functionDataIndex, $functionData)) {
                    $branchData = $functionData[$functionDataIndex]['branches'];

                    foreach ($branchData as $branchNumber => $data) {
                        $lineNumber = $data['line_start'];

                        if (!array_key_exists($lineNumber, $lines)) {
                            $lines[$lineNumber] = ['branches' => []];
                        }

                        $lines[$lineNumber]['branches'][] = [
                            'block'  => 0,
                            'branch' => $branchNumber,
                            'count'  => empty($data['hit']) ? '-' : count($data['hit']),
                        ];
                    }
                }
            }
        }

        foreach ($coverageData as $line => $data) {
            if ($data === null || isset($lines[$line])) {
                continue;
            }

            $lines[$line] = ['count' => count($data), 'type' => 'stmt'];
        }

        ksort($lines);

        return $lines;
    }
}
