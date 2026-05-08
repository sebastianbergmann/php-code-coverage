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
use function count;
use function range;
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

/**
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @phpstan-import-type TestType from CodeCoverage
 * @phpstan-import-type LinesType from AnalysisResult
 */
final class File extends AbstractNode
{
    /**
     * @var non-empty-string
     */
    private string $sha1;

    /**
     * @var array<positive-int, ?list<non-empty-string>>
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
    private int $numExecutableLines                          = 0;
    private int $numExecutedLines                            = 0;
    private int $numExecutedLinesBySmallTests                = 0;
    private int $numExecutedLinesByMediumTests               = 0;
    private int $numExecutedLinesByLargeTests                = 0;
    private int $numExecutedLinesBySmallOrMediumTests        = 0;
    private int $numExecutedLinesBySmallOrLargeTests         = 0;
    private int $numExecutedLinesByMediumOrLargeTests        = 0;
    private int $numExecutedLinesBySmallOrMediumOrLargeTests = 0;
    private int $numExecutableBranches                       = 0;
    private int $numExecutedBranches                         = 0;
    private int $numExecutablePaths                          = 0;
    private int $numExecutedPaths                            = 0;

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
    private ?int $numClasses                                    = null;
    private int $numTestedClasses                               = 0;
    private int $numTestedClassesBySmallTests                   = 0;
    private int $numTestedClassesByMediumTests                  = 0;
    private int $numTestedClassesByLargeTests                   = 0;
    private int $numTestedClassesBySmallOrMediumTests           = 0;
    private int $numTestedClassesBySmallOrLargeTests            = 0;
    private int $numTestedClassesByMediumOrLargeTests           = 0;
    private int $numTestedClassesBySmallOrMediumOrLargeTests    = 0;
    private ?int $numTraits                                     = null;
    private int $numTestedTraits                                = 0;
    private int $numTestedTraitsBySmallTests                    = 0;
    private int $numTestedTraitsByMediumTests                   = 0;
    private int $numTestedTraitsByLargeTests                    = 0;
    private int $numTestedTraitsBySmallOrMediumTests            = 0;
    private int $numTestedTraitsBySmallOrLargeTests             = 0;
    private int $numTestedTraitsByMediumOrLargeTests            = 0;
    private int $numTestedTraitsBySmallOrMediumOrLargeTests     = 0;
    private ?int $numMethods                                    = null;
    private ?int $numTestedMethods                              = null;
    private ?int $numTestedMethodsBySmallTests                  = null;
    private ?int $numTestedMethodsByMediumTests                 = null;
    private ?int $numTestedMethodsByLargeTests                  = null;
    private ?int $numTestedMethodsBySmallOrMediumTests          = null;
    private ?int $numTestedMethodsBySmallOrLargeTests           = null;
    private ?int $numTestedMethodsByMediumOrLargeTests          = null;
    private ?int $numTestedMethodsBySmallOrMediumOrLargeTests   = null;
    private int $numTestedFunctions                             = 0;
    private ?int $numTestedFunctionsBySmallTests                = null;
    private ?int $numTestedFunctionsByMediumTests               = null;
    private ?int $numTestedFunctionsByLargeTests                = null;
    private ?int $numTestedFunctionsBySmallOrMediumTests        = null;
    private ?int $numTestedFunctionsBySmallOrLargeTests         = null;
    private ?int $numTestedFunctionsByMediumOrLargeTests        = null;
    private ?int $numTestedFunctionsBySmallOrMediumOrLargeTests = null;

    /**
     * @var array<int, array<int, ProcessedClassType|ProcessedFunctionType|ProcessedMethodType|ProcessedTraitType>>
     */
    private array $codeUnitsByLine = [];

    /**
     * @param non-empty-string                                       $sha1
     * @param array<positive-int, ?list<non-empty-string>>           $lineCoverageData
     * @param array<non-empty-string, ProcessedFunctionCoverageData> $functionCoverageData
     * @param array<non-empty-string, TestType>                      $testData
     * @param array<string, Class_>                                  $classes
     * @param array<string, Trait_>                                  $traits
     * @param array<string, Function_>                               $functions
     */
    public function __construct(string $name, AbstractNode $parent, string $sha1, array $lineCoverageData, array $functionCoverageData, array $testData, array $classes, array $traits, array $functions, LinesOfCode $linesOfCode, bool $hasBranchCoverageData = false)
    {
        parent::__construct($name, $parent);

        $this->sha1                  = $sha1;
        $this->lineCoverageData      = $lineCoverageData;
        $this->functionCoverageData  = $functionCoverageData;
        $this->testData              = $testData;
        $this->linesOfCode           = $linesOfCode;
        $this->hasBranchCoverageData = $hasBranchCoverageData;

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
     * @return array<positive-int, ?list<non-empty-string>>
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

    public function numberOfExecutedLinesBySmallTests(): int
    {
        return $this->numExecutedLinesBySmallTests;
    }

    public function numberOfExecutedLinesByMediumTests(): int
    {
        return $this->numExecutedLinesByMediumTests;
    }

    public function numberOfExecutedLinesByLargeTests(): int
    {
        return $this->numExecutedLinesByLargeTests;
    }

    public function numberOfExecutedLinesBySmallOrMediumTests(): int
    {
        return $this->numExecutedLinesBySmallOrMediumTests;
    }

    public function numberOfExecutedLinesBySmallOrLargeTests(): int
    {
        return $this->numExecutedLinesBySmallOrLargeTests;
    }

    public function numberOfExecutedLinesByMediumOrLargeTests(): int
    {
        return $this->numExecutedLinesByMediumOrLargeTests;
    }

    public function numberOfExecutedLinesBySmallOrMediumOrLargeTests(): int
    {
        return $this->numExecutedLinesBySmallOrMediumOrLargeTests;
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

    public function numberOfTestedClassesBySmallTests(): int
    {
        return $this->numTestedClassesBySmallTests;
    }

    public function numberOfTestedClassesByMediumTests(): int
    {
        return $this->numTestedClassesByMediumTests;
    }

    public function numberOfTestedClassesByLargeTests(): int
    {
        return $this->numTestedClassesByLargeTests;
    }

    public function numberOfTestedClassesBySmallOrMediumTests(): int
    {
        return $this->numTestedClassesBySmallOrMediumTests;
    }

    public function numberOfTestedClassesBySmallOrLargeTests(): int
    {
        return $this->numTestedClassesBySmallOrLargeTests;
    }

    public function numberOfTestedClassesByMediumOrLargeTests(): int
    {
        return $this->numTestedClassesByMediumOrLargeTests;
    }

    public function numberOfTestedClassesBySmallOrMediumOrLargeTests(): int
    {
        return $this->numTestedClassesBySmallOrMediumOrLargeTests;
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

    public function numberOfTestedTraitsBySmallTests(): int
    {
        return $this->numTestedTraitsBySmallTests;
    }

    public function numberOfTestedTraitsByMediumTests(): int
    {
        return $this->numTestedTraitsByMediumTests;
    }

    public function numberOfTestedTraitsByLargeTests(): int
    {
        return $this->numTestedTraitsByLargeTests;
    }

    public function numberOfTestedTraitsBySmallOrMediumTests(): int
    {
        return $this->numTestedTraitsBySmallOrMediumTests;
    }

    public function numberOfTestedTraitsBySmallOrLargeTests(): int
    {
        return $this->numTestedTraitsBySmallOrLargeTests;
    }

    public function numberOfTestedTraitsByMediumOrLargeTests(): int
    {
        return $this->numTestedTraitsByMediumOrLargeTests;
    }

    public function numberOfTestedTraitsBySmallOrMediumOrLargeTests(): int
    {
        return $this->numTestedTraitsBySmallOrMediumOrLargeTests;
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

    public function numberOfTestedMethodsBySmallTests(): int
    {
        if ($this->numTestedMethodsBySmallTests === null) {
            $this->numTestedMethodsBySmallTests = 0;

            foreach ($this->classes as $class) {
                foreach ($class->methods as $method) {
                    if ($method->executableLines > 0 && $method->executedLinesBySmallTests === $method->executableLines) {
                        $this->numTestedMethodsBySmallTests++;
                    }
                }
            }

            foreach ($this->traits as $trait) {
                foreach ($trait->methods as $method) {
                    if ($method->executableLines > 0 && $method->executedLinesBySmallTests === $method->executableLines) {
                        $this->numTestedMethodsBySmallTests++;
                    }
                }
            }
        }

        return $this->numTestedMethodsBySmallTests;
    }

    public function numberOfTestedMethodsByMediumTests(): int
    {
        if ($this->numTestedMethodsByMediumTests === null) {
            $this->numTestedMethodsByMediumTests = 0;

            foreach ($this->classes as $class) {
                foreach ($class->methods as $method) {
                    if ($method->executableLines > 0 && $method->executedLinesByMediumTests === $method->executableLines) {
                        $this->numTestedMethodsByMediumTests++;
                    }
                }
            }

            foreach ($this->traits as $trait) {
                foreach ($trait->methods as $method) {
                    if ($method->executableLines > 0 && $method->executedLinesByMediumTests === $method->executableLines) {
                        $this->numTestedMethodsByMediumTests++;
                    }
                }
            }
        }

        return $this->numTestedMethodsByMediumTests;
    }

    public function numberOfTestedMethodsByLargeTests(): int
    {
        if ($this->numTestedMethodsByLargeTests === null) {
            $this->numTestedMethodsByLargeTests = 0;

            foreach ($this->classes as $class) {
                foreach ($class->methods as $method) {
                    if ($method->executableLines > 0 && $method->executedLinesByLargeTests === $method->executableLines) {
                        $this->numTestedMethodsByLargeTests++;
                    }
                }
            }

            foreach ($this->traits as $trait) {
                foreach ($trait->methods as $method) {
                    if ($method->executableLines > 0 && $method->executedLinesByLargeTests === $method->executableLines) {
                        $this->numTestedMethodsByLargeTests++;
                    }
                }
            }
        }

        return $this->numTestedMethodsByLargeTests;
    }

    public function numberOfTestedMethodsBySmallOrMediumTests(): int
    {
        if ($this->numTestedMethodsBySmallOrMediumTests === null) {
            $this->numTestedMethodsBySmallOrMediumTests = 0;

            foreach ($this->classes as $class) {
                foreach ($class->methods as $method) {
                    if ($method->executableLines > 0 && $method->executedLinesBySmallOrMediumTests === $method->executableLines) {
                        $this->numTestedMethodsBySmallOrMediumTests++;
                    }
                }
            }

            foreach ($this->traits as $trait) {
                foreach ($trait->methods as $method) {
                    if ($method->executableLines > 0 && $method->executedLinesBySmallOrMediumTests === $method->executableLines) {
                        $this->numTestedMethodsBySmallOrMediumTests++;
                    }
                }
            }
        }

        return $this->numTestedMethodsBySmallOrMediumTests;
    }

    public function numberOfTestedMethodsBySmallOrLargeTests(): int
    {
        if ($this->numTestedMethodsBySmallOrLargeTests === null) {
            $this->numTestedMethodsBySmallOrLargeTests = 0;

            foreach ($this->classes as $class) {
                foreach ($class->methods as $method) {
                    if ($method->executableLines > 0 && $method->executedLinesBySmallOrLargeTests === $method->executableLines) {
                        $this->numTestedMethodsBySmallOrLargeTests++;
                    }
                }
            }

            foreach ($this->traits as $trait) {
                foreach ($trait->methods as $method) {
                    if ($method->executableLines > 0 && $method->executedLinesBySmallOrLargeTests === $method->executableLines) {
                        $this->numTestedMethodsBySmallOrLargeTests++;
                    }
                }
            }
        }

        return $this->numTestedMethodsBySmallOrLargeTests;
    }

    public function numberOfTestedMethodsByMediumOrLargeTests(): int
    {
        if ($this->numTestedMethodsByMediumOrLargeTests === null) {
            $this->numTestedMethodsByMediumOrLargeTests = 0;

            foreach ($this->classes as $class) {
                foreach ($class->methods as $method) {
                    if ($method->executableLines > 0 && $method->executedLinesByMediumOrLargeTests === $method->executableLines) {
                        $this->numTestedMethodsByMediumOrLargeTests++;
                    }
                }
            }

            foreach ($this->traits as $trait) {
                foreach ($trait->methods as $method) {
                    if ($method->executableLines > 0 && $method->executedLinesByMediumOrLargeTests === $method->executableLines) {
                        $this->numTestedMethodsByMediumOrLargeTests++;
                    }
                }
            }
        }

        return $this->numTestedMethodsByMediumOrLargeTests;
    }

    public function numberOfTestedMethodsBySmallOrMediumOrLargeTests(): int
    {
        if ($this->numTestedMethodsBySmallOrMediumOrLargeTests === null) {
            $this->numTestedMethodsBySmallOrMediumOrLargeTests = 0;

            foreach ($this->classes as $class) {
                foreach ($class->methods as $method) {
                    if ($method->executableLines > 0 && $method->executedLinesBySmallOrMediumOrLargeTests === $method->executableLines) {
                        $this->numTestedMethodsBySmallOrMediumOrLargeTests++;
                    }
                }
            }

            foreach ($this->traits as $trait) {
                foreach ($trait->methods as $method) {
                    if ($method->executableLines > 0 && $method->executedLinesBySmallOrMediumOrLargeTests === $method->executableLines) {
                        $this->numTestedMethodsBySmallOrMediumOrLargeTests++;
                    }
                }
            }
        }

        return $this->numTestedMethodsBySmallOrMediumOrLargeTests;
    }

    public function numberOfFunctions(): int
    {
        return count($this->functions);
    }

    public function numberOfTestedFunctions(): int
    {
        return $this->numTestedFunctions;
    }

    public function numberOfTestedFunctionsBySmallTests(): int
    {
        if ($this->numTestedFunctionsBySmallTests === null) {
            $this->numTestedFunctionsBySmallTests = 0;

            foreach ($this->functions as $function) {
                if ($function->executableLines > 0 && $function->executedLinesBySmallTests === $function->executableLines) {
                    $this->numTestedFunctionsBySmallTests++;
                }
            }
        }

        return $this->numTestedFunctionsBySmallTests;
    }

    public function numberOfTestedFunctionsByMediumTests(): int
    {
        if ($this->numTestedFunctionsByMediumTests === null) {
            $this->numTestedFunctionsByMediumTests = 0;

            foreach ($this->functions as $function) {
                if ($function->executableLines > 0 && $function->executedLinesByMediumTests === $function->executableLines) {
                    $this->numTestedFunctionsByMediumTests++;
                }
            }
        }

        return $this->numTestedFunctionsByMediumTests;
    }

    public function numberOfTestedFunctionsByLargeTests(): int
    {
        if ($this->numTestedFunctionsByLargeTests === null) {
            $this->numTestedFunctionsByLargeTests = 0;

            foreach ($this->functions as $function) {
                if ($function->executableLines > 0 && $function->executedLinesByLargeTests === $function->executableLines) {
                    $this->numTestedFunctionsByLargeTests++;
                }
            }
        }

        return $this->numTestedFunctionsByLargeTests;
    }

    public function numberOfTestedFunctionsBySmallOrMediumTests(): int
    {
        if ($this->numTestedFunctionsBySmallOrMediumTests === null) {
            $this->numTestedFunctionsBySmallOrMediumTests = 0;

            foreach ($this->functions as $function) {
                if ($function->executableLines > 0 && $function->executedLinesBySmallOrMediumTests === $function->executableLines) {
                    $this->numTestedFunctionsBySmallOrMediumTests++;
                }
            }
        }

        return $this->numTestedFunctionsBySmallOrMediumTests;
    }

    public function numberOfTestedFunctionsBySmallOrLargeTests(): int
    {
        if ($this->numTestedFunctionsBySmallOrLargeTests === null) {
            $this->numTestedFunctionsBySmallOrLargeTests = 0;

            foreach ($this->functions as $function) {
                if ($function->executableLines > 0 && $function->executedLinesBySmallOrLargeTests === $function->executableLines) {
                    $this->numTestedFunctionsBySmallOrLargeTests++;
                }
            }
        }

        return $this->numTestedFunctionsBySmallOrLargeTests;
    }

    public function numberOfTestedFunctionsByMediumOrLargeTests(): int
    {
        if ($this->numTestedFunctionsByMediumOrLargeTests === null) {
            $this->numTestedFunctionsByMediumOrLargeTests = 0;

            foreach ($this->functions as $function) {
                if ($function->executableLines > 0 && $function->executedLinesByMediumOrLargeTests === $function->executableLines) {
                    $this->numTestedFunctionsByMediumOrLargeTests++;
                }
            }
        }

        return $this->numTestedFunctionsByMediumOrLargeTests;
    }

    public function numberOfTestedFunctionsBySmallOrMediumOrLargeTests(): int
    {
        if ($this->numTestedFunctionsBySmallOrMediumOrLargeTests === null) {
            $this->numTestedFunctionsBySmallOrMediumOrLargeTests = 0;

            foreach ($this->functions as $function) {
                if ($function->executableLines > 0 && $function->executedLinesBySmallOrMediumOrLargeTests === $function->executableLines) {
                    $this->numTestedFunctionsBySmallOrMediumOrLargeTests++;
                }
            }
        }

        return $this->numTestedFunctionsBySmallOrMediumOrLargeTests;
    }

    /**
     * @param array<string, Class_>    $classes
     * @param array<string, Trait_>    $traits
     * @param array<string, Function_> $functions
     */
    private function calculateStatistics(array $classes, array $traits, array $functions): void
    {
        foreach (range(1, $this->linesOfCode->linesOfCode()) as $lineNumber) {
            $this->codeUnitsByLine[$lineNumber] = [];
        }

        $this->processClasses($classes);
        $this->processTraits($traits);
        $this->processFunctions($functions);

        foreach (range(1, $this->linesOfCode->linesOfCode()) as $lineNumber) {
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

                    $coveredBySmall  = false;
                    $coveredByMedium = false;
                    $coveredByLarge  = false;

                    foreach ($this->lineCoverageData[$lineNumber] as $testId) {
                        if (isset($this->testData[$testId])) {
                            $size = $this->testData[$testId]['size'];

                            if ($size === 'small') {
                                $coveredBySmall = true;
                            } elseif ($size === 'medium') {
                                $coveredByMedium = true;
                            } elseif ($size === 'large') {
                                $coveredByLarge = true;
                            }
                        }
                    }

                    if ($coveredBySmall) {
                        $this->numExecutedLinesBySmallTests++;

                        foreach ($this->codeUnitsByLine[$lineNumber] as &$codeUnit) {
                            $codeUnit->executedLinesBySmallTests++;
                        }

                        unset($codeUnit);
                    }

                    if ($coveredByMedium) {
                        $this->numExecutedLinesByMediumTests++;

                        foreach ($this->codeUnitsByLine[$lineNumber] as &$codeUnit) {
                            $codeUnit->executedLinesByMediumTests++;
                        }

                        unset($codeUnit);
                    }

                    if ($coveredByLarge) {
                        $this->numExecutedLinesByLargeTests++;

                        foreach ($this->codeUnitsByLine[$lineNumber] as &$codeUnit) {
                            $codeUnit->executedLinesByLargeTests++;
                        }

                        unset($codeUnit);
                    }

                    if ($coveredBySmall || $coveredByMedium) {
                        $this->numExecutedLinesBySmallOrMediumTests++;

                        foreach ($this->codeUnitsByLine[$lineNumber] as &$codeUnit) {
                            $codeUnit->executedLinesBySmallOrMediumTests++;
                        }

                        unset($codeUnit);
                    }

                    if ($coveredBySmall || $coveredByLarge) {
                        $this->numExecutedLinesBySmallOrLargeTests++;

                        foreach ($this->codeUnitsByLine[$lineNumber] as &$codeUnit) {
                            $codeUnit->executedLinesBySmallOrLargeTests++;
                        }

                        unset($codeUnit);
                    }

                    if ($coveredByMedium || $coveredByLarge) {
                        $this->numExecutedLinesByMediumOrLargeTests++;

                        foreach ($this->codeUnitsByLine[$lineNumber] as &$codeUnit) {
                            $codeUnit->executedLinesByMediumOrLargeTests++;
                        }

                        unset($codeUnit);
                    }

                    if ($coveredBySmall || $coveredByMedium || $coveredByLarge) {
                        $this->numExecutedLinesBySmallOrMediumOrLargeTests++;

                        foreach ($this->codeUnitsByLine[$lineNumber] as &$codeUnit) {
                            $codeUnit->executedLinesBySmallOrMediumOrLargeTests++;
                        }

                        unset($codeUnit);
                    }
                }
            }
        }

        foreach ($this->traits as &$trait) {
            foreach ($trait->methods as &$method) {
                $methodLineCoverage   = $method->executableLines > 0 ? ($method->executedLines / $method->executableLines) * 100 : 100;
                $methodBranchCoverage = $method->executableBranches > 0 ? ($method->executedBranches / $method->executableBranches) * 100 : 0;
                $methodPathCoverage   = $method->executablePaths > 0 ? ($method->executedPaths / $method->executablePaths) * 100 : 0;

                $method->coverage = $methodBranchCoverage > 0 ? $methodBranchCoverage : $methodLineCoverage;
                $method->crap     = new CrapIndex($method->ccn, $methodPathCoverage > 0 ? $methodPathCoverage : $methodLineCoverage)->asString();

                $trait->ccn += $method->ccn;
            }

            unset($method);

            $traitBranchCoverage = $trait->executableBranches > 0 ? ($trait->executedBranches / $trait->executableBranches) * 100 : 0;
            $traitLineCoverage   = $trait->executableLines > 0 ? ($trait->executedLines / $trait->executableLines) * 100 : 100;
            $traitPathCoverage   = $trait->executablePaths > 0 ? ($trait->executedPaths / $trait->executablePaths) * 100 : 0;

            $trait->coverage = $traitBranchCoverage > 0 ? $traitBranchCoverage : $traitLineCoverage;
            $trait->crap     = new CrapIndex($trait->ccn, $traitPathCoverage > 0 ? $traitPathCoverage : $traitLineCoverage)->asString();

            if ($trait->executableLines > 0 && $trait->coverage === 100) {
                $this->numTestedTraits++;
            }

            if ($trait->executableLines > 0 && $trait->executedLinesBySmallTests === $trait->executableLines) {
                $this->numTestedTraitsBySmallTests++;
            }

            if ($trait->executableLines > 0 && $trait->executedLinesByMediumTests === $trait->executableLines) {
                $this->numTestedTraitsByMediumTests++;
            }

            if ($trait->executableLines > 0 && $trait->executedLinesByLargeTests === $trait->executableLines) {
                $this->numTestedTraitsByLargeTests++;
            }

            if ($trait->executableLines > 0 && $trait->executedLinesBySmallOrMediumTests === $trait->executableLines) {
                $this->numTestedTraitsBySmallOrMediumTests++;
            }

            if ($trait->executableLines > 0 && $trait->executedLinesBySmallOrLargeTests === $trait->executableLines) {
                $this->numTestedTraitsBySmallOrLargeTests++;
            }

            if ($trait->executableLines > 0 && $trait->executedLinesByMediumOrLargeTests === $trait->executableLines) {
                $this->numTestedTraitsByMediumOrLargeTests++;
            }

            if ($trait->executableLines > 0 && $trait->executedLinesBySmallOrMediumOrLargeTests === $trait->executableLines) {
                $this->numTestedTraitsBySmallOrMediumOrLargeTests++;
            }
        }

        unset($trait);

        foreach ($this->classes as &$class) {
            foreach ($class->methods as &$method) {
                $methodLineCoverage   = $method->executableLines > 0 ? ($method->executedLines / $method->executableLines) * 100 : 100;
                $methodBranchCoverage = $method->executableBranches > 0 ? ($method->executedBranches / $method->executableBranches) * 100 : 0;
                $methodPathCoverage   = $method->executablePaths > 0 ? ($method->executedPaths / $method->executablePaths) * 100 : 0;

                $method->coverage = $methodBranchCoverage > 0 ? $methodBranchCoverage : $methodLineCoverage;
                $method->crap     = new CrapIndex($method->ccn, $methodPathCoverage > 0 ? $methodPathCoverage : $methodLineCoverage)->asString();

                $class->ccn += $method->ccn;
            }

            unset($method);

            $classLineCoverage   = $class->executableLines > 0 ? ($class->executedLines / $class->executableLines) * 100 : 100;
            $classBranchCoverage = $class->executableBranches > 0 ? ($class->executedBranches / $class->executableBranches) * 100 : 0;
            $classPathCoverage   = $class->executablePaths > 0 ? ($class->executedPaths / $class->executablePaths) * 100 : 0;

            $class->coverage = $classBranchCoverage > 0 ? $classBranchCoverage : $classLineCoverage;
            $class->crap     = new CrapIndex($class->ccn, $classPathCoverage > 0 ? $classPathCoverage : $classLineCoverage)->asString();

            if ($class->executableLines > 0 && $class->coverage === 100) {
                $this->numTestedClasses++;
            }

            if ($class->executableLines > 0 && $class->executedLinesBySmallTests === $class->executableLines) {
                $this->numTestedClassesBySmallTests++;
            }

            if ($class->executableLines > 0 && $class->executedLinesByMediumTests === $class->executableLines) {
                $this->numTestedClassesByMediumTests++;
            }

            if ($class->executableLines > 0 && $class->executedLinesByLargeTests === $class->executableLines) {
                $this->numTestedClassesByLargeTests++;
            }

            if ($class->executableLines > 0 && $class->executedLinesBySmallOrMediumTests === $class->executableLines) {
                $this->numTestedClassesBySmallOrMediumTests++;
            }

            if ($class->executableLines > 0 && $class->executedLinesBySmallOrLargeTests === $class->executableLines) {
                $this->numTestedClassesBySmallOrLargeTests++;
            }

            if ($class->executableLines > 0 && $class->executedLinesByMediumOrLargeTests === $class->executableLines) {
                $this->numTestedClassesByMediumOrLargeTests++;
            }

            if ($class->executableLines > 0 && $class->executedLinesBySmallOrMediumOrLargeTests === $class->executableLines) {
                $this->numTestedClassesBySmallOrMediumOrLargeTests++;
            }
        }

        unset($class);

        foreach ($this->functions as &$function) {
            $functionLineCoverage   = $function->executableLines > 0 ? ($function->executedLines / $function->executableLines) * 100 : 100;
            $functionBranchCoverage = $function->executableBranches > 0 ? ($function->executedBranches / $function->executableBranches) * 100 : 0;
            $functionPathCoverage   = $function->executablePaths > 0 ? ($function->executedPaths / $function->executablePaths) * 100 : 0;

            $function->coverage = $functionBranchCoverage > 0 ? $functionBranchCoverage : $functionLineCoverage;
            $function->crap     = new CrapIndex($function->ccn, $functionPathCoverage > 0 ? $functionPathCoverage : $functionLineCoverage)->asString();

            if ($function->coverage === 100) {
                $this->numTestedFunctions++;
            }

            if ($function->executableLines > 0 && $function->executedLinesBySmallTests === $function->executableLines) {
                $this->numTestedFunctionsBySmallTests++;
            }

            if ($function->executableLines > 0 && $function->executedLinesByMediumTests === $function->executableLines) {
                $this->numTestedFunctionsByMediumTests++;
            }

            if ($function->executableLines > 0 && $function->executedLinesByLargeTests === $function->executableLines) {
                $this->numTestedFunctionsByLargeTests++;
            }

            if ($function->executableLines > 0 && $function->executedLinesBySmallOrMediumTests === $function->executableLines) {
                $this->numTestedFunctionsBySmallOrMediumTests++;
            }

            if ($function->executableLines > 0 && $function->executedLinesBySmallOrLargeTests === $function->executableLines) {
                $this->numTestedFunctionsBySmallOrLargeTests++;
            }

            if ($function->executableLines > 0 && $function->executedLinesByMediumOrLargeTests === $function->executableLines) {
                $this->numTestedFunctionsByMediumOrLargeTests++;
            }

            if ($function->executableLines > 0 && $function->executedLinesBySmallOrMediumOrLargeTests === $function->executableLines) {
                $this->numTestedFunctionsBySmallOrMediumOrLargeTests++;
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
            $this->classes[$className] = new ProcessedClassType(
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

            foreach ($class->methods() as $methodName => $method) {
                $methodData                                      = $this->newMethod($className, $method, $link);
                $this->classes[$className]->methods[$methodName] = $methodData;

                $this->classes[$className]->executableBranches += $methodData->executableBranches;
                $this->classes[$className]->executedBranches   += $methodData->executedBranches;
                $this->classes[$className]->executablePaths    += $methodData->executablePaths;
                $this->classes[$className]->executedPaths      += $methodData->executedPaths;

                $this->numExecutableBranches += $methodData->executableBranches;
                $this->numExecutedBranches   += $methodData->executedBranches;
                $this->numExecutablePaths    += $methodData->executablePaths;
                $this->numExecutedPaths      += $methodData->executedPaths;

                foreach (range($method->startLine(), $method->endLine()) as $lineNumber) {
                    $this->codeUnitsByLine[$lineNumber] = [
                        &$this->classes[$className],
                        &$this->classes[$className]->methods[$methodName],
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
            $this->traits[$traitName] = new ProcessedTraitType(
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

            foreach ($trait->methods() as $methodName => $method) {
                $methodData                                     = $this->newMethod($traitName, $method, $link);
                $this->traits[$traitName]->methods[$methodName] = $methodData;

                $this->traits[$traitName]->executableBranches += $methodData->executableBranches;
                $this->traits[$traitName]->executedBranches   += $methodData->executedBranches;
                $this->traits[$traitName]->executablePaths    += $methodData->executablePaths;
                $this->traits[$traitName]->executedPaths      += $methodData->executedPaths;

                $this->numExecutableBranches += $methodData->executableBranches;
                $this->numExecutedBranches   += $methodData->executedBranches;
                $this->numExecutablePaths    += $methodData->executablePaths;
                $this->numExecutedPaths      += $methodData->executedPaths;

                foreach (range($method->startLine(), $method->endLine()) as $lineNumber) {
                    $this->codeUnitsByLine[$lineNumber] = [
                        &$this->traits[$traitName],
                        &$this->traits[$traitName]->methods[$methodName],
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
            $this->functions[$functionName] = new ProcessedFunctionType(
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

            foreach (range($function->startLine(), $function->endLine()) as $lineNumber) {
                $this->codeUnitsByLine[$lineNumber] = [&$this->functions[$functionName]];
            }

            if (isset($this->functionCoverageData[$functionName])) {
                $this->functions[$functionName]->executableBranches = count(
                    $this->functionCoverageData[$functionName]->branches,
                );

                $this->functions[$functionName]->executedBranches = count(
                    array_filter(
                        $this->functionCoverageData[$functionName]->branches,
                        static function (ProcessedBranchCoverageData $branch)
                        {
                            return (bool) $branch->hit;
                        },
                    ),
                );
            }

            if (isset($this->functionCoverageData[$functionName])) {
                $this->functions[$functionName]->executablePaths = count(
                    $this->functionCoverageData[$functionName]->paths,
                );

                $this->functions[$functionName]->executedPaths = count(
                    array_filter(
                        $this->functionCoverageData[$functionName]->paths,
                        static function (ProcessedPathCoverageData $path)
                        {
                            return (bool) $path->hit;
                        },
                    ),
                );
            }

            $this->numExecutableBranches += $this->functions[$functionName]->executableBranches;
            $this->numExecutedBranches   += $this->functions[$functionName]->executedBranches;
            $this->numExecutablePaths    += $this->functions[$functionName]->executablePaths;
            $this->numExecutedPaths      += $this->functions[$functionName]->executedPaths;
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
