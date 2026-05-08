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

use const DIRECTORY_SEPARATOR;
use function array_merge;
use function max;
use function str_ends_with;
use function str_replace;
use function substr;
use Countable;
use SebastianBergmann\CodeCoverage\Data\ProcessedClassType;
use SebastianBergmann\CodeCoverage\Data\ProcessedFunctionType;
use SebastianBergmann\CodeCoverage\Data\ProcessedTraitType;
use SebastianBergmann\CodeCoverage\StaticAnalysis\LinesOfCode;
use SebastianBergmann\CodeCoverage\Util\Percentage;

/**
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for phpunit/php-code-coverage
 */
abstract class AbstractNode implements Countable
{
    private readonly string $name;
    private string $pathAsString;

    /**
     * @var non-empty-list<self>
     */
    private array $pathAsArray;
    private readonly ?AbstractNode $parent;
    private string $id;

    public function __construct(string $name, ?self $parent = null)
    {
        if (str_ends_with($name, DIRECTORY_SEPARATOR)) {
            $name = substr($name, 0, -1);
        }

        $this->name   = $name;
        $this->parent = $parent;

        $this->processId();
        $this->processPath();
    }

    public function name(): string
    {
        return $this->name;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function pathAsString(): string
    {
        return $this->pathAsString;
    }

    /**
     * @return non-empty-list<self>
     */
    public function pathAsArray(): array
    {
        return $this->pathAsArray;
    }

    public function parent(): ?self
    {
        return $this->parent;
    }

    public function percentageOfTestedClasses(): Percentage
    {
        return Percentage::fromFractionAndTotal(
            $this->numberOfTestedClasses(),
            $this->numberOfClasses(),
        );
    }

    public function percentageOfTestedTraits(): Percentage
    {
        return Percentage::fromFractionAndTotal(
            $this->numberOfTestedTraits(),
            $this->numberOfTraits(),
        );
    }

    public function percentageOfTestedClassesAndTraits(): Percentage
    {
        return Percentage::fromFractionAndTotal(
            $this->numberOfTestedClassesAndTraits(),
            $this->numberOfClassesAndTraits(),
        );
    }

    public function percentageOfTestedFunctions(): Percentage
    {
        return Percentage::fromFractionAndTotal(
            $this->numberOfTestedFunctions(),
            $this->numberOfFunctions(),
        );
    }

    public function percentageOfTestedMethods(): Percentage
    {
        return Percentage::fromFractionAndTotal(
            $this->numberOfTestedMethods(),
            $this->numberOfMethods(),
        );
    }

    public function percentageOfTestedFunctionsAndMethods(): Percentage
    {
        return Percentage::fromFractionAndTotal(
            $this->numberOfTestedFunctionsAndMethods(),
            $this->numberOfFunctionsAndMethods(),
        );
    }

    public function percentageOfExecutedLines(): Percentage
    {
        return Percentage::fromFractionAndTotal(
            $this->numberOfExecutedLines(),
            $this->numberOfExecutableLines(),
        );
    }

    public function percentageOfExecutedLinesBySmallTests(): Percentage
    {
        return Percentage::fromFractionAndTotal(
            $this->numberOfExecutedLinesBySmallTests(),
            $this->numberOfExecutableLines(),
        );
    }

    public function percentageOfExecutedLinesByMediumTests(): Percentage
    {
        return Percentage::fromFractionAndTotal(
            $this->numberOfExecutedLinesByMediumTests(),
            $this->numberOfExecutableLines(),
        );
    }

    public function percentageOfExecutedLinesByLargeTests(): Percentage
    {
        return Percentage::fromFractionAndTotal(
            $this->numberOfExecutedLinesByLargeTests(),
            $this->numberOfExecutableLines(),
        );
    }

    public function percentageOfExecutedLinesBySmallOrMediumTests(): Percentage
    {
        return Percentage::fromFractionAndTotal(
            $this->numberOfExecutedLinesBySmallOrMediumTests(),
            $this->numberOfExecutableLines(),
        );
    }

    public function percentageOfExecutedLinesBySmallOrLargeTests(): Percentage
    {
        return Percentage::fromFractionAndTotal(
            $this->numberOfExecutedLinesBySmallOrLargeTests(),
            $this->numberOfExecutableLines(),
        );
    }

    public function percentageOfExecutedLinesByMediumOrLargeTests(): Percentage
    {
        return Percentage::fromFractionAndTotal(
            $this->numberOfExecutedLinesByMediumOrLargeTests(),
            $this->numberOfExecutableLines(),
        );
    }

    public function percentageOfExecutedLinesBySmallOrMediumOrLargeTests(): Percentage
    {
        return Percentage::fromFractionAndTotal(
            $this->numberOfExecutedLinesBySmallOrMediumOrLargeTests(),
            $this->numberOfExecutableLines(),
        );
    }

    public function percentageOfExecutedBranches(): Percentage
    {
        return Percentage::fromFractionAndTotal(
            $this->numberOfExecutedBranches(),
            $this->numberOfExecutableBranches(),
        );
    }

    public function percentageOfExecutedPaths(): Percentage
    {
        return Percentage::fromFractionAndTotal(
            $this->numberOfExecutedPaths(),
            $this->numberOfExecutablePaths(),
        );
    }

    public function numberOfClassesAndTraits(): int
    {
        return $this->numberOfClasses() + $this->numberOfTraits();
    }

    public function numberOfTestedClassesAndTraits(): int
    {
        return $this->numberOfTestedClasses() + $this->numberOfTestedTraits();
    }

    public function numberOfTestedClassesAndTraitsBySmallTests(): int
    {
        return $this->numberOfTestedClassesBySmallTests() + $this->numberOfTestedTraitsBySmallTests();
    }

    public function numberOfTestedClassesAndTraitsByMediumTests(): int
    {
        return $this->numberOfTestedClassesByMediumTests() + $this->numberOfTestedTraitsByMediumTests();
    }

    public function numberOfTestedClassesAndTraitsByLargeTests(): int
    {
        return $this->numberOfTestedClassesByLargeTests() + $this->numberOfTestedTraitsByLargeTests();
    }

    public function numberOfTestedClassesAndTraitsBySmallOrMediumTests(): int
    {
        return $this->numberOfTestedClassesBySmallOrMediumTests() + $this->numberOfTestedTraitsBySmallOrMediumTests();
    }

    public function numberOfTestedClassesAndTraitsBySmallOrLargeTests(): int
    {
        return $this->numberOfTestedClassesBySmallOrLargeTests() + $this->numberOfTestedTraitsBySmallOrLargeTests();
    }

    public function numberOfTestedClassesAndTraitsByMediumOrLargeTests(): int
    {
        return $this->numberOfTestedClassesByMediumOrLargeTests() + $this->numberOfTestedTraitsByMediumOrLargeTests();
    }

    public function numberOfTestedClassesAndTraitsBySmallOrMediumOrLargeTests(): int
    {
        return $this->numberOfTestedClassesBySmallOrMediumOrLargeTests() + $this->numberOfTestedTraitsBySmallOrMediumOrLargeTests();
    }

    /**
     * @return array<string, ProcessedClassType|ProcessedTraitType>
     */
    public function classesAndTraits(): array
    {
        return array_merge($this->classes(), $this->traits());
    }

    public function numberOfFunctionsAndMethods(): int
    {
        return $this->numberOfFunctions() + $this->numberOfMethods();
    }

    public function numberOfTestedFunctionsAndMethods(): int
    {
        return $this->numberOfTestedFunctions() + $this->numberOfTestedMethods();
    }

    public function numberOfTestedFunctionsAndMethodsBySmallTests(): int
    {
        return $this->numberOfTestedFunctionsBySmallTests() + $this->numberOfTestedMethodsBySmallTests();
    }

    public function numberOfTestedFunctionsAndMethodsByMediumTests(): int
    {
        return $this->numberOfTestedFunctionsByMediumTests() + $this->numberOfTestedMethodsByMediumTests();
    }

    public function numberOfTestedFunctionsAndMethodsByLargeTests(): int
    {
        return $this->numberOfTestedFunctionsByLargeTests() + $this->numberOfTestedMethodsByLargeTests();
    }

    public function numberOfTestedFunctionsAndMethodsBySmallOrMediumTests(): int
    {
        return $this->numberOfTestedFunctionsBySmallOrMediumTests() + $this->numberOfTestedMethodsBySmallOrMediumTests();
    }

    public function numberOfTestedFunctionsAndMethodsBySmallOrLargeTests(): int
    {
        return $this->numberOfTestedFunctionsBySmallOrLargeTests() + $this->numberOfTestedMethodsBySmallOrLargeTests();
    }

    public function numberOfTestedFunctionsAndMethodsByMediumOrLargeTests(): int
    {
        return $this->numberOfTestedFunctionsByMediumOrLargeTests() + $this->numberOfTestedMethodsByMediumOrLargeTests();
    }

    public function numberOfTestedFunctionsAndMethodsBySmallOrMediumOrLargeTests(): int
    {
        return $this->numberOfTestedFunctionsBySmallOrMediumOrLargeTests() + $this->numberOfTestedMethodsBySmallOrMediumOrLargeTests();
    }

    /**
     * @return non-negative-int
     */
    public function cyclomaticComplexity(): int
    {
        $ccn = 0;

        foreach ($this->classesAndTraits() as $classLike) {
            $ccn += $classLike->ccn;
        }

        foreach ($this->functions() as $function) {
            $ccn += $function->ccn;
        }

        return max(0, $ccn);
    }

    /**
     * @return array<string, ProcessedClassType>
     */
    abstract public function classes(): array;

    /**
     * @return array<string, ProcessedTraitType>
     */
    abstract public function traits(): array;

    /**
     * @return array<string, ProcessedFunctionType>
     */
    abstract public function functions(): array;

    abstract public function linesOfCode(): LinesOfCode;

    abstract public function numberOfExecutableLines(): int;

    abstract public function numberOfExecutedLines(): int;

    abstract public function numberOfExecutedLinesBySmallTests(): int;

    abstract public function numberOfExecutedLinesByMediumTests(): int;

    abstract public function numberOfExecutedLinesByLargeTests(): int;

    abstract public function numberOfExecutedLinesBySmallOrMediumTests(): int;

    abstract public function numberOfExecutedLinesBySmallOrLargeTests(): int;

    abstract public function numberOfExecutedLinesByMediumOrLargeTests(): int;

    abstract public function numberOfExecutedLinesBySmallOrMediumOrLargeTests(): int;

    abstract public function numberOfExecutableBranches(): int;

    abstract public function numberOfExecutedBranches(): int;

    abstract public function numberOfExecutablePaths(): int;

    abstract public function numberOfExecutedPaths(): int;

    abstract public function numberOfFilesWithoutBranchCoverageData(): int;

    abstract public function numberOfClasses(): int;

    abstract public function numberOfTestedClasses(): int;

    abstract public function numberOfTestedClassesBySmallTests(): int;

    abstract public function numberOfTestedClassesByMediumTests(): int;

    abstract public function numberOfTestedClassesByLargeTests(): int;

    abstract public function numberOfTestedClassesBySmallOrMediumTests(): int;

    abstract public function numberOfTestedClassesBySmallOrLargeTests(): int;

    abstract public function numberOfTestedClassesByMediumOrLargeTests(): int;

    abstract public function numberOfTestedClassesBySmallOrMediumOrLargeTests(): int;

    abstract public function numberOfTraits(): int;

    abstract public function numberOfTestedTraits(): int;

    abstract public function numberOfTestedTraitsBySmallTests(): int;

    abstract public function numberOfTestedTraitsByMediumTests(): int;

    abstract public function numberOfTestedTraitsByLargeTests(): int;

    abstract public function numberOfTestedTraitsBySmallOrMediumTests(): int;

    abstract public function numberOfTestedTraitsBySmallOrLargeTests(): int;

    abstract public function numberOfTestedTraitsByMediumOrLargeTests(): int;

    abstract public function numberOfTestedTraitsBySmallOrMediumOrLargeTests(): int;

    abstract public function numberOfMethods(): int;

    abstract public function numberOfTestedMethods(): int;

    abstract public function numberOfTestedMethodsBySmallTests(): int;

    abstract public function numberOfTestedMethodsByMediumTests(): int;

    abstract public function numberOfTestedMethodsByLargeTests(): int;

    abstract public function numberOfTestedMethodsBySmallOrMediumTests(): int;

    abstract public function numberOfTestedMethodsBySmallOrLargeTests(): int;

    abstract public function numberOfTestedMethodsByMediumOrLargeTests(): int;

    abstract public function numberOfTestedMethodsBySmallOrMediumOrLargeTests(): int;

    abstract public function numberOfFunctions(): int;

    abstract public function numberOfTestedFunctions(): int;

    abstract public function numberOfTestedFunctionsBySmallTests(): int;

    abstract public function numberOfTestedFunctionsByMediumTests(): int;

    abstract public function numberOfTestedFunctionsByLargeTests(): int;

    abstract public function numberOfTestedFunctionsBySmallOrMediumTests(): int;

    abstract public function numberOfTestedFunctionsBySmallOrLargeTests(): int;

    abstract public function numberOfTestedFunctionsByMediumOrLargeTests(): int;

    abstract public function numberOfTestedFunctionsBySmallOrMediumOrLargeTests(): int;

    private function processId(): void
    {
        if ($this->parent === null) {
            $this->id = 'index';

            return;
        }

        $parentId = $this->parent->id();

        if ($parentId === 'index') {
            $this->id = str_replace(':', '_', $this->name);
        } else {
            $this->id = $parentId . '/' . $this->name;
        }
    }

    private function processPath(): void
    {
        if ($this->parent === null) {
            $this->pathAsArray  = [$this];
            $this->pathAsString = $this->name;

            return;
        }

        $this->pathAsArray  = $this->parent->pathAsArray();
        $this->pathAsString = $this->parent->pathAsString() . DIRECTORY_SEPARATOR . $this->name;

        $this->pathAsArray[] = $this;
    }
}
