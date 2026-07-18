<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\Node;

use function array_filter;
use function array_keys;
use function count;
use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Data\ProcessedBranchCoverageData;
use SebastianBergmann\CodeCoverage\Data\ProcessedClassType;
use SebastianBergmann\CodeCoverage\Data\ProcessedFunctionCoverageData;
use SebastianBergmann\CodeCoverage\Data\ProcessedFunctionType;
use SebastianBergmann\CodeCoverage\Data\ProcessedMethodType;
use SebastianBergmann\CodeCoverage\Data\ProcessedPathCoverageData;
use SebastianBergmann\CodeCoverage\Data\ProcessedTraitType;
use SebastianBergmann\CodeCoverage\StaticAnalysis\AnalysisResult;
use SebastianBergmann\CodeCoverage\StaticAnalysis\Class_;
use SebastianBergmann\CodeCoverage\StaticAnalysis\Function_;
use SebastianBergmann\CodeCoverage\StaticAnalysis\LinesOfCode;
use SebastianBergmann\CodeCoverage\StaticAnalysis\Method;
use SebastianBergmann\CodeCoverage\StaticAnalysis\Trait_;
use SebastianBergmann\CodeCoverage\Test\TestSizes;

/**
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @phpstan-import-type TestType from CodeCoverage
 * @phpstan-import-type LinesType from AnalysisResult
 * @phpstan-import-type TestSizeSet from TestSizes
 * @phpstan-import-type TestSizeCounts from TestSizes
 */
final class File extends AbstractNode
{
    /**
     * @var non-empty-string
     */
    private string $sha1;

    /**
     * @var array<positive-int, ?array<non-empty-string, positive-int>>
     */
    private array $lineCoverageData;

    /**
     * @var array<non-empty-string, ProcessedFunctionCoverageData>
     */
    private array $functionCoverageData;

    /**
     * @var array<non-empty-string, TestType>
     */
    private readonly array $testData;
    private int $numExecutableLines = 0;
    private int $numExecutedLines   = 0;

    /**
     * @var TestSizeCounts
     */
    private array $numExecutedLinesByTestSize = TestSizes::ZERO_COUNTS;
    private int $numExecutableBranches        = 0;
    private int $numExecutedBranches          = 0;
    private int $numExecutablePaths           = 0;
    private int $numExecutedPaths             = 0;

    /**
     * @var array<string, ProcessedClassType>
     */
    private array $classes = [];

    /**
     * @var array<string, ProcessedTraitType>
     */
    private array $traits = [];

    /**
     * @var array<string, ProcessedFunctionType>
     */
    private array $functions = [];
    private readonly LinesOfCode $linesOfCode;
    private readonly bool $hasBranchCoverageData;
    private readonly bool $collectsHitCounts;
    private ?int $numClasses      = null;
    private int $numTestedClasses = 0;

    /**
     * @var TestSizeCounts
     */
    private array $numTestedClassesByTestSize = TestSizes::ZERO_COUNTS;
    private ?int $numTraits                   = null;
    private int $numTestedTraits              = 0;

    /**
     * @var TestSizeCounts
     */
    private array $numTestedTraitsByTestSize = TestSizes::ZERO_COUNTS;
    private ?int $numMethods                 = null;
    private ?int $numTestedMethods           = null;

    /**
     * @var ?TestSizeCounts
     */
    private ?array $numTestedMethodsByTestSize = null;
    private int $numTestedFunctions            = 0;

    /**
     * @var TestSizeCounts
     */
    private array $numTestedFunctionsByTestSize = TestSizes::ZERO_COUNTS;

    /**
     * @var array<int, array<int, ProcessedClassType|ProcessedFunctionType|ProcessedMethodType|ProcessedTraitType>>
     */
    private array $codeUnitsByLine = [];

    /**
     * @var array<string, Class_>
     */
    private readonly array $rawClasses;

    /**
     * @var array<string, Trait_>
     */
    private readonly array $rawTraits;

    /**
     * @param non-empty-string                                            $sha1
     * @param array<positive-int, ?array<non-empty-string, positive-int>> $lineCoverageData
     * @param array<non-empty-string, ProcessedFunctionCoverageData>      $functionCoverageData
     * @param array<non-empty-string, TestType>                           $testData
     * @param array<string, Class_>                                       $classes
     * @param array<string, Trait_>                                       $traits
     * @param array<string, Function_>                                    $functions
     */
    public function __construct(string $name, AbstractNode $parent, string $sha1, array $lineCoverageData, array $functionCoverageData, array $testData, array $classes, array $traits, array $functions, LinesOfCode $linesOfCode, bool $hasBranchCoverageData = false, bool $collectsHitCounts = false)
    {
        parent::__construct($name, $parent);

        $this->sha1                  = $sha1;
        $this->lineCoverageData      = $lineCoverageData;
        $this->functionCoverageData  = $functionCoverageData;
        $this->testData              = $testData;
        $this->linesOfCode           = $linesOfCode;
        $this->hasBranchCoverageData = $hasBranchCoverageData;
        $this->collectsHitCounts     = $collectsHitCounts;
        $this->rawClasses            = $classes;
        $this->rawTraits             = $traits;

        $this->calculateStatistics($classes, $traits, $functions);
    }

    public function count(): int
    {
        return 1;
    }

    /**
     * @return non-empty-string
     */
    public function sha1(): string
    {
        return $this->sha1;
    }

    /**
     * @return array<positive-int, ?array<non-empty-string, positive-int>>
     */
    public function lineCoverageData(): array
    {
        return $this->lineCoverageData;
    }

    /**
     * @return array<non-empty-string, ProcessedFunctionCoverageData>
     */
    public function functionCoverageData(): array
    {
        return $this->functionCoverageData;
    }

    /**
     * @return array<non-empty-string, TestType>
     */
    public function testData(): array
    {
        return $this->testData;
    }

    /**
     * @return array<string, Class_>
     */
    public function rawClasses(): array
    {
        return $this->rawClasses;
    }

    /**
     * @return array<string, Trait_>
     */
    public function rawTraits(): array
    {
        return $this->rawTraits;
    }

    /**
     * @return array<string, ProcessedClassType>
     */
    public function classes(): array
    {
        return $this->classes;
    }

    /**
     * @return array<string, ProcessedTraitType>
     */
    public function traits(): array
    {
        return $this->traits;
    }

    /**
     * @return array<string, ProcessedFunctionType>
     */
    public function functions(): array
    {
        return $this->functions;
    }

    public function linesOfCode(): LinesOfCode
    {
        return $this->linesOfCode;
    }

    public function numberOfExecutableLines(): int
    {
        return $this->numExecutableLines;
    }

    public function numberOfExecutedLines(): int
    {
        return $this->numExecutedLines;
    }

    /**
     * @param TestSizeSet $testSizes
     */
    public function numberOfExecutedLinesByTestSize(int $testSizes): int
    {
        return $this->numExecutedLinesByTestSize[$testSizes];
    }

    public function numberOfExecutableBranches(): int
    {
        return $this->numExecutableBranches;
    }

    public function numberOfExecutedBranches(): int
    {
        return $this->numExecutedBranches;
    }

    public function numberOfExecutablePaths(): int
    {
        return $this->numExecutablePaths;
    }

    public function numberOfExecutedPaths(): int
    {
        return $this->numExecutedPaths;
    }

    public function hasBranchCoverageData(): bool
    {
        return $this->hasBranchCoverageData;
    }

    /**
     * Whether the values in lineCoverageData() are exact execution counts (the driver that
     * collected the data counts how often a line was executed) or only mean "executed at
     * least once".
     */
    public function collectsHitCounts(): bool
    {
        return $this->collectsHitCounts;
    }

    public function numberOfFilesWithoutBranchCoverageData(): int
    {
        return $this->hasBranchCoverageData ? 0 : 1;
    }

    public function numberOfClasses(): int
    {
        if ($this->numClasses === null) {
            $this->numClasses = 0;

            foreach ($this->classes as $class) {
                foreach ($class->methods as $method) {
                    if ($method->executableLines > 0) {
                        $this->numClasses++;

                        continue 2;
                    }
                }
            }
        }

        return $this->numClasses;
    }

    public function numberOfTestedClasses(): int
    {
        return $this->numTestedClasses;
    }

    /**
     * @param TestSizeSet $testSizes
     */
    public function numberOfTestedClassesByTestSize(int $testSizes): int
    {
        return $this->numTestedClassesByTestSize[$testSizes];
    }

    public function numberOfTraits(): int
    {
        if ($this->numTraits === null) {
            $this->numTraits = 0;

            foreach ($this->traits as $trait) {
                foreach ($trait->methods as $method) {
                    if ($method->executableLines > 0) {
                        $this->numTraits++;

                        continue 2;
                    }
                }
            }
        }

        return $this->numTraits;
    }

    public function numberOfTestedTraits(): int
    {
        return $this->numTestedTraits;
    }

    /**
     * @param TestSizeSet $testSizes
     */
    public function numberOfTestedTraitsByTestSize(int $testSizes): int
    {
        return $this->numTestedTraitsByTestSize[$testSizes];
    }

    public function numberOfMethods(): int
    {
        if ($this->numMethods === null) {
            $this->numMethods = 0;

            foreach ($this->classes as $class) {
                foreach ($class->methods as $method) {
                    if ($method->executableLines > 0) {
                        $this->numMethods++;
                    }
                }
            }

            foreach ($this->traits as $trait) {
                foreach ($trait->methods as $method) {
                    if ($method->executableLines > 0) {
                        $this->numMethods++;
                    }
                }
            }
        }

        return $this->numMethods;
    }

    public function numberOfTestedMethods(): int
    {
        if ($this->numTestedMethods === null) {
            $this->numTestedMethods = 0;

            foreach ($this->classes as $class) {
                foreach ($class->methods as $method) {
                    if ($method->executableLines > 0 &&
                        $method->coverage === 100) {
                        $this->numTestedMethods++;
                    }
                }
            }

            foreach ($this->traits as $trait) {
                foreach ($trait->methods as $method) {
                    if ($method->executableLines > 0 &&
                        $method->coverage === 100) {
                        $this->numTestedMethods++;
                    }
                }
            }
        }

        return $this->numTestedMethods;
    }

    /**
     * @param TestSizeSet $testSizes
     */
    public function numberOfTestedMethodsByTestSize(int $testSizes): int
    {
        if ($this->numTestedMethodsByTestSize === null) {
            $this->numTestedMethodsByTestSize = TestSizes::ZERO_COUNTS;

            foreach ([$this->classes, $this->traits] as $classesOrTraits) {
                foreach ($classesOrTraits as $classOrTrait) {
                    foreach ($classOrTrait->methods as $method) {
                        if ($method->executableLines === 0) {
                            continue;
                        }

                        foreach (TestSizes::COMBINATIONS as $combination) {
                            if ($method->executedLinesByTestSize[$combination] === $method->executableLines) {
                                $this->numTestedMethodsByTestSize[$combination]++;
                            }
                        }
                    }
                }
            }
        }

        return $this->numTestedMethodsByTestSize[$testSizes];
    }

    public function numberOfFunctions(): int
    {
        return count($this->functions);
    }

    public function numberOfTestedFunctions(): int
    {
        return $this->numTestedFunctions;
    }

    /**
     * @param TestSizeSet $testSizes
     */
    public function numberOfTestedFunctionsByTestSize(int $testSizes): int
    {
        return $this->numTestedFunctionsByTestSize[$testSizes];
    }

    /**
     * @param array<string, Class_>    $classes
     * @param array<string, Trait_>    $traits
     * @param array<string, Function_> $functions
     */
    private function calculateStatistics(array $classes, array $traits, array $functions): void
    {
        $this->processClasses($classes);
        $this->processTraits($traits);
        $this->processFunctions($functions);

        $linesOfCode = $this->linesOfCode->linesOfCode();

        for ($lineNumber = 1; $lineNumber <= $linesOfCode; $lineNumber++) {
            if (isset($this->lineCoverageData[$lineNumber])) {
                foreach ($this->codeUnitsByLine[$lineNumber] ?? [] as $codeUnit) {
                    $codeUnit->executableLines++;
                }

                $this->numExecutableLines++;

                if (count($this->lineCoverageData[$lineNumber]) > 0) {
                    foreach ($this->codeUnitsByLine[$lineNumber] ?? [] as $codeUnit) {
                        $codeUnit->executedLines++;
                    }

                    $this->numExecutedLines++;

                    $coveringTestSizes = 0;

                    foreach (array_keys($this->lineCoverageData[$lineNumber]) as $testId) {
                        if (isset($this->testData[$testId])) {
                            $coveringTestSizes |= TestSizes::bitFor($this->testData[$testId]['size']);

                            if ($coveringTestSizes === TestSizes::ALL) {
                                break;
                            }
                        }
                    }

                    if ($coveringTestSizes !== 0) {
                        foreach (TestSizes::COMBINATIONS as $combination) {
                            if (($combination & $coveringTestSizes) === 0) {
                                continue;
                            }

                            $this->numExecutedLinesByTestSize[$combination]++;

                            foreach ($this->codeUnitsByLine[$lineNumber] ?? [] as $codeUnit) {
                                $codeUnit->executedLinesByTestSize[$combination]++;
                            }
                        }
                    }
                }
            }
        }

        foreach ($this->traits as $trait) {
            foreach ($trait->methods as $method) {
                $methodLineCoverage   = $method->executableLines > 0 ? ($method->executedLines / $method->executableLines) * 100 : 100;
                $methodBranchCoverage = $method->executableBranches > 0 ? ($method->executedBranches / $method->executableBranches) * 100 : 0;
                $methodPathCoverage   = $method->executablePaths > 0 ? ($method->executedPaths / $method->executablePaths) * 100 : 0;

                $method->coverage = $methodBranchCoverage > 0 ? $methodBranchCoverage : $methodLineCoverage;
                $method->crap     = new CrapIndex($method->ccn, $methodPathCoverage > 0 ? $methodPathCoverage : $methodLineCoverage)->asString();

                $trait->ccn += $method->ccn;
            }

            $traitBranchCoverage = $trait->executableBranches > 0 ? ($trait->executedBranches / $trait->executableBranches) * 100 : 0;
            $traitLineCoverage   = $trait->executableLines > 0 ? ($trait->executedLines / $trait->executableLines) * 100 : 100;
            $traitPathCoverage   = $trait->executablePaths > 0 ? ($trait->executedPaths / $trait->executablePaths) * 100 : 0;

            $trait->coverage = $traitBranchCoverage > 0 ? $traitBranchCoverage : $traitLineCoverage;
            $trait->crap     = new CrapIndex($trait->ccn, $traitPathCoverage > 0 ? $traitPathCoverage : $traitLineCoverage)->asString();

            if ($trait->executableLines > 0 && $trait->coverage === 100) {
                $this->numTestedTraits++;
            }

            if ($trait->executableLines > 0) {
                foreach (TestSizes::COMBINATIONS as $combination) {
                    if ($trait->executedLinesByTestSize[$combination] === $trait->executableLines) {
                        $this->numTestedTraitsByTestSize[$combination]++;
                    }
                }
            }
        }

        foreach ($this->classes as $class) {
            foreach ($class->methods as $method) {
                $methodLineCoverage   = $method->executableLines > 0 ? ($method->executedLines / $method->executableLines) * 100 : 100;
                $methodBranchCoverage = $method->executableBranches > 0 ? ($method->executedBranches / $method->executableBranches) * 100 : 0;
                $methodPathCoverage   = $method->executablePaths > 0 ? ($method->executedPaths / $method->executablePaths) * 100 : 0;

                $method->coverage = $methodBranchCoverage > 0 ? $methodBranchCoverage : $methodLineCoverage;
                $method->crap     = new CrapIndex($method->ccn, $methodPathCoverage > 0 ? $methodPathCoverage : $methodLineCoverage)->asString();

                $class->ccn += $method->ccn;
            }

            $classLineCoverage   = $class->executableLines > 0 ? ($class->executedLines / $class->executableLines) * 100 : 100;
            $classBranchCoverage = $class->executableBranches > 0 ? ($class->executedBranches / $class->executableBranches) * 100 : 0;
            $classPathCoverage   = $class->executablePaths > 0 ? ($class->executedPaths / $class->executablePaths) * 100 : 0;

            $class->coverage = $classBranchCoverage > 0 ? $classBranchCoverage : $classLineCoverage;
            $class->crap     = new CrapIndex($class->ccn, $classPathCoverage > 0 ? $classPathCoverage : $classLineCoverage)->asString();

            if ($class->executableLines > 0 && $class->coverage === 100) {
                $this->numTestedClasses++;
            }

            if ($class->executableLines > 0) {
                foreach (TestSizes::COMBINATIONS as $combination) {
                    if ($class->executedLinesByTestSize[$combination] === $class->executableLines) {
                        $this->numTestedClassesByTestSize[$combination]++;
                    }
                }
            }
        }

        foreach ($this->functions as $function) {
            $functionLineCoverage   = $function->executableLines > 0 ? ($function->executedLines / $function->executableLines) * 100 : 100;
            $functionBranchCoverage = $function->executableBranches > 0 ? ($function->executedBranches / $function->executableBranches) * 100 : 0;
            $functionPathCoverage   = $function->executablePaths > 0 ? ($function->executedPaths / $function->executablePaths) * 100 : 0;

            $function->coverage = $functionBranchCoverage > 0 ? $functionBranchCoverage : $functionLineCoverage;
            $function->crap     = new CrapIndex($function->ccn, $functionPathCoverage > 0 ? $functionPathCoverage : $functionLineCoverage)->asString();

            if ($function->coverage === 100) {
                $this->numTestedFunctions++;
            }

            if ($function->executableLines > 0) {
                foreach (TestSizes::COMBINATIONS as $combination) {
                    if ($function->executedLinesByTestSize[$combination] === $function->executableLines) {
                        $this->numTestedFunctionsByTestSize[$combination]++;
                    }
                }
            }
        }
    }

    /**
     * @param array<string, Class_> $classes
     */
    private function processClasses(array $classes): void
    {
        $link = $this->id() . '.html#';

        foreach ($classes as $className => $class) {
            $classData = new ProcessedClassType(
                $className,
                $class->namespace(),
                [],
                $class->startLine(),
                0,
                0,
                0,
                0,
                0,
                0,
                0,
                0,
                0,
                $link . $class->startLine(),
            );

            $this->classes[$className] = $classData;

            foreach ($class->methods() as $methodName => $method) {
                $methodData                      = $this->newMethod($className, $method, $link);
                $classData->methods[$methodName] = $methodData;

                $classData->executableBranches += $methodData->executableBranches;
                $classData->executedBranches   += $methodData->executedBranches;
                $classData->executablePaths    += $methodData->executablePaths;
                $classData->executedPaths      += $methodData->executedPaths;

                $this->numExecutableBranches += $methodData->executableBranches;
                $this->numExecutedBranches   += $methodData->executedBranches;
                $this->numExecutablePaths    += $methodData->executablePaths;
                $this->numExecutedPaths      += $methodData->executedPaths;

                $methodEndLine = $method->endLine();

                for ($lineNumber = $method->startLine(); $lineNumber <= $methodEndLine; $lineNumber++) {
                    $this->codeUnitsByLine[$lineNumber] = [
                        $classData,
                        $methodData,
                    ];
                }
            }
        }
    }

    /**
     * @param array<string, Trait_> $traits
     */
    private function processTraits(array $traits): void
    {
        $link = $this->id() . '.html#';

        foreach ($traits as $traitName => $trait) {
            $traitData = new ProcessedTraitType(
                $traitName,
                $trait->namespace(),
                [],
                $trait->startLine(),
                0,
                0,
                0,
                0,
                0,
                0,
                0,
                0,
                0,
                $link . $trait->startLine(),
            );

            $this->traits[$traitName] = $traitData;

            foreach ($trait->methods() as $methodName => $method) {
                $methodData                      = $this->newMethod($traitName, $method, $link);
                $traitData->methods[$methodName] = $methodData;

                $traitData->executableBranches += $methodData->executableBranches;
                $traitData->executedBranches   += $methodData->executedBranches;
                $traitData->executablePaths    += $methodData->executablePaths;
                $traitData->executedPaths      += $methodData->executedPaths;

                $this->numExecutableBranches += $methodData->executableBranches;
                $this->numExecutedBranches   += $methodData->executedBranches;
                $this->numExecutablePaths    += $methodData->executablePaths;
                $this->numExecutedPaths      += $methodData->executedPaths;

                $methodEndLine = $method->endLine();

                for ($lineNumber = $method->startLine(); $lineNumber <= $methodEndLine; $lineNumber++) {
                    $this->codeUnitsByLine[$lineNumber] = [
                        $traitData,
                        $methodData,
                    ];
                }
            }
        }
    }

    /**
     * @param array<string, Function_> $functions
     */
    private function processFunctions(array $functions): void
    {
        $link = $this->id() . '.html#';

        foreach ($functions as $functionName => $function) {
            $functionData = new ProcessedFunctionType(
                $functionName,
                $function->namespace(),
                $function->signature(),
                $function->startLine(),
                $function->endLine(),
                0,
                0,
                0,
                0,
                0,
                0,
                $function->cyclomaticComplexity(),
                0,
                0,
                $link . $function->startLine(),
            );

            $this->functions[$functionName] = $functionData;

            $functionEndLine = $function->endLine();

            for ($lineNumber = $function->startLine(); $lineNumber <= $functionEndLine; $lineNumber++) {
                $this->codeUnitsByLine[$lineNumber] = [$functionData];
            }

            if (isset($this->functionCoverageData[$functionName])) {
                $functionData->executableBranches = count(
                    $this->functionCoverageData[$functionName]->branches,
                );

                $functionData->executedBranches = count(
                    array_filter(
                        $this->functionCoverageData[$functionName]->branches,
                        static function (ProcessedBranchCoverageData $branch)
                        {
                            return (bool) $branch->hit;
                        },
                    ),
                );

                $functionData->executablePaths = count(
                    $this->functionCoverageData[$functionName]->paths,
                );

                $functionData->executedPaths = count(
                    array_filter(
                        $this->functionCoverageData[$functionName]->paths,
                        static function (ProcessedPathCoverageData $path)
                        {
                            return (bool) $path->hit;
                        },
                    ),
                );
            }

            $this->numExecutableBranches += $functionData->executableBranches;
            $this->numExecutedBranches   += $functionData->executedBranches;
            $this->numExecutablePaths    += $functionData->executablePaths;
            $this->numExecutedPaths      += $functionData->executedPaths;
        }
    }

    private function newMethod(string $className, Method $method, string $link): ProcessedMethodType
    {
        $key = $className . '->' . $method->name();

        $executableBranches = 0;
        $executedBranches   = 0;

        if (isset($this->functionCoverageData[$key])) {
            $executableBranches = count(
                $this->functionCoverageData[$key]->branches,
            );

            $executedBranches = count(
                array_filter(
                    $this->functionCoverageData[$key]->branches,
                    static function (ProcessedBranchCoverageData $branch)
                    {
                        return (bool) $branch->hit;
                    },
                ),
            );
        }

        $executablePaths = 0;
        $executedPaths   = 0;

        if (isset($this->functionCoverageData[$key])) {
            $executablePaths = count(
                $this->functionCoverageData[$key]->paths,
            );

            $executedPaths = count(
                array_filter(
                    $this->functionCoverageData[$key]->paths,
                    static function (ProcessedPathCoverageData $path)
                    {
                        return (bool) $path->hit;
                    },
                ),
            );
        }

        return new ProcessedMethodType(
            $method->name(),
            $method->visibility()->value,
            $method->signature(),
            $method->startLine(),
            $method->endLine(),
            0,
            0,
            $executableBranches,
            $executedBranches,
            $executablePaths,
            $executedPaths,
            $method->cyclomaticComplexity(),
            0,
            0,
            $link . $method->startLine(),
        );
    }
}
