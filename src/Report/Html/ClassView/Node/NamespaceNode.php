<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\Report\Html\ClassView\Node;

use function array_merge;
use function str_replace;
use Generator;
use SebastianBergmann\CodeCoverage\Data\ProcessedClassType;
use SebastianBergmann\CodeCoverage\Data\ProcessedMethodType;
use SebastianBergmann\CodeCoverage\Data\ProcessedTraitType;
use SebastianBergmann\CodeCoverage\Util\Percentage;

/**
 * Aggregated metrics count the own code of each class in the namespace exactly once and each
 * trait used by those classes exactly once, even when a trait is used by multiple classes;
 * code inherited from a parent class is counted at the node of the class that declares it.
 * ClassNode metrics, in contrast, include the code a class uses from traits and inherits from
 * parent classes, so the metrics of the classes in a namespace may add up to more than the
 * metrics of the namespace itself.
 *
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for phpunit/php-code-coverage
 */
final class NamespaceNode
{
    private readonly string $name;
    private readonly string $namespace;
    private ?self $parent;

    /**
     * @var list<self>
     */
    private array $childNamespaces = [];

    /**
     * @var list<ClassNode>
     */
    private array $classes             = [];
    private int $numExecutableLines    = -1;
    private int $numExecutedLines      = -1;
    private int $numExecutableBranches = -1;
    private int $numExecutedBranches   = -1;
    private int $numExecutablePaths    = -1;
    private int $numExecutedPaths      = -1;
    private int $numClasses            = -1;
    private int $numTestedClasses      = -1;
    private int $numMethods            = -1;
    private int $numTestedMethods      = -1;

    public function __construct(string $name, string $namespace, ?self $parent = null)
    {
        $this->name      = $name;
        $this->namespace = $namespace;
        $this->parent    = $parent;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function namespace(): string
    {
        return $this->namespace;
    }

    public function parent(): ?self
    {
        return $this->parent;
    }

    public function promoteToRoot(): void
    {
        $this->parent = null;
    }

    public function id(): string
    {
        if ($this->parent === null) {
            return 'index';
        }

        $parentId = $this->parent->id();

        if ($parentId === 'index') {
            return str_replace('\\', '_', $this->name);
        }

        return $parentId . '/' . str_replace('\\', '_', $this->name);
    }

    /**
     * @return non-empty-list<self>
     */
    public function pathAsArray(): array
    {
        if ($this->parent === null) {
            return [$this];
        }

        $path   = $this->parent->pathAsArray();
        $path[] = $this;

        return $path;
    }

    public function addNamespace(self $namespace): void
    {
        $this->childNamespaces[] = $namespace;
        $this->resetCounters();
    }

    public function addClass(ClassNode $class): void
    {
        $this->classes[] = $class;
        $this->resetCounters();
    }

    /**
     * @return list<self>
     */
    public function childNamespaces(): array
    {
        return $this->childNamespaces;
    }

    /**
     * @return list<ClassNode>
     */
    public function classes(): array
    {
        return $this->classes;
    }

    public function numberOfExecutableLines(): int
    {
        if ($this->numExecutableLines === -1) {
            $this->numExecutableLines = 0;

            foreach ($this->classesInSubtree() as $class) {
                $this->numExecutableLines += $class->class_()->executableLines;
            }

            foreach ($this->traitsInSubtree() as $trait) {
                $this->numExecutableLines += $trait->executableLines;
            }
        }

        return $this->numExecutableLines;
    }

    public function numberOfExecutedLines(): int
    {
        if ($this->numExecutedLines === -1) {
            $this->numExecutedLines = 0;

            foreach ($this->classesInSubtree() as $class) {
                $this->numExecutedLines += $class->class_()->executedLines;
            }

            foreach ($this->traitsInSubtree() as $trait) {
                $this->numExecutedLines += $trait->executedLines;
            }
        }

        return $this->numExecutedLines;
    }

    public function numberOfExecutableBranches(): int
    {
        if ($this->numExecutableBranches === -1) {
            $this->numExecutableBranches = 0;

            foreach ($this->classesInSubtree() as $class) {
                $this->numExecutableBranches += $class->class_()->executableBranches;
            }

            foreach ($this->traitsInSubtree() as $trait) {
                $this->numExecutableBranches += $trait->executableBranches;
            }
        }

        return $this->numExecutableBranches;
    }

    public function numberOfExecutedBranches(): int
    {
        if ($this->numExecutedBranches === -1) {
            $this->numExecutedBranches = 0;

            foreach ($this->classesInSubtree() as $class) {
                $this->numExecutedBranches += $class->class_()->executedBranches;
            }

            foreach ($this->traitsInSubtree() as $trait) {
                $this->numExecutedBranches += $trait->executedBranches;
            }
        }

        return $this->numExecutedBranches;
    }

    public function numberOfExecutablePaths(): int
    {
        if ($this->numExecutablePaths === -1) {
            $this->numExecutablePaths = 0;

            foreach ($this->classesInSubtree() as $class) {
                $this->numExecutablePaths += $class->class_()->executablePaths;
            }

            foreach ($this->traitsInSubtree() as $trait) {
                $this->numExecutablePaths += $trait->executablePaths;
            }
        }

        return $this->numExecutablePaths;
    }

    public function numberOfExecutedPaths(): int
    {
        if ($this->numExecutedPaths === -1) {
            $this->numExecutedPaths = 0;

            foreach ($this->classesInSubtree() as $class) {
                $this->numExecutedPaths += $class->class_()->executedPaths;
            }

            foreach ($this->traitsInSubtree() as $trait) {
                $this->numExecutedPaths += $trait->executedPaths;
            }
        }

        return $this->numExecutedPaths;
    }

    public function numberOfClasses(): int
    {
        if ($this->numClasses === -1) {
            $this->numClasses = 0;

            foreach ($this->classes as $class) {
                if ($class->numberOfMethods() > 0) {
                    $this->numClasses++;
                }
            }

            foreach ($this->childNamespaces as $ns) {
                $this->numClasses += $ns->numberOfClasses();
            }
        }

        return $this->numClasses;
    }

    public function numberOfTestedClasses(): int
    {
        if ($this->numTestedClasses === -1) {
            $this->numTestedClasses = 0;

            foreach ($this->classes as $class) {
                if ($class->numberOfMethods() > 0 && $class->numberOfTestedMethods() === $class->numberOfMethods()) {
                    $this->numTestedClasses++;
                }
            }

            foreach ($this->childNamespaces as $ns) {
                $this->numTestedClasses += $ns->numberOfTestedClasses();
            }
        }

        return $this->numTestedClasses;
    }

    public function numberOfMethods(): int
    {
        if ($this->numMethods === -1) {
            $this->numMethods = 0;

            foreach ($this->methodsInSubtree() as $method) {
                if ($method->executableLines > 0) {
                    $this->numMethods++;
                }
            }
        }

        return $this->numMethods;
    }

    public function numberOfTestedMethods(): int
    {
        if ($this->numTestedMethods === -1) {
            $this->numTestedMethods = 0;

            foreach ($this->methodsInSubtree() as $method) {
                if ($method->executableLines > 0 && $method->coverage === 100) {
                    $this->numTestedMethods++;
                }
            }
        }

        return $this->numTestedMethods;
    }

    public function percentageOfExecutedLines(): Percentage
    {
        return Percentage::fromFractionAndTotal(
            $this->numberOfExecutedLines(),
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

    public function percentageOfTestedMethods(): Percentage
    {
        return Percentage::fromFractionAndTotal(
            $this->numberOfTestedMethods(),
            $this->numberOfMethods(),
        );
    }

    public function percentageOfTestedClasses(): Percentage
    {
        return Percentage::fromFractionAndTotal(
            $this->numberOfTestedClasses(),
            $this->numberOfClasses(),
        );
    }

    /**
     * @return array<string, ProcessedClassType>
     */
    public function allClassTypes(): array
    {
        $result = [];

        foreach ($this->classes as $class) {
            $result[$class->className()] = $class->class_();
        }

        foreach ($this->childNamespaces as $ns) {
            $result = array_merge($result, $ns->allClassTypes());
        }

        return $result;
    }

    /**
     * Yields all ClassNode and NamespaceNode descendants (depth-first).
     *
     * @return Generator<ClassNode|self>
     */
    public function iterate(): Generator
    {
        foreach ($this->childNamespaces as $ns) {
            yield $ns;

            yield from $ns->iterate();
        }

        foreach ($this->classes as $class) {
            yield $class;
        }
    }

    /**
     * @return list<ClassNode>
     */
    private function classesInSubtree(): array
    {
        $classes = $this->classes;

        foreach ($this->childNamespaces as $ns) {
            $classes = array_merge($classes, $ns->classesInSubtree());
        }

        return $classes;
    }

    /**
     * @return array<non-empty-string, ProcessedTraitType>
     */
    private function traitsInSubtree(): array
    {
        $traits = [];

        foreach ($this->classesInSubtree() as $class) {
            foreach ($class->traitSections() as $section) {
                $traits[$section->traitName] = $section->trait;
            }
        }

        return $traits;
    }

    /**
     * @return list<ProcessedMethodType>
     */
    private function methodsInSubtree(): array
    {
        $methods = [];

        foreach ($this->classesInSubtree() as $class) {
            foreach ($class->class_()->methods as $method) {
                $methods[] = $method;
            }
        }

        foreach ($this->traitsInSubtree() as $trait) {
            foreach ($trait->methods as $method) {
                $methods[] = $method;
            }
        }

        return $methods;
    }

    private function resetCounters(): void
    {
        $this->numExecutableLines    = -1;
        $this->numExecutedLines      = -1;
        $this->numExecutableBranches = -1;
        $this->numExecutedBranches   = -1;
        $this->numExecutablePaths    = -1;
        $this->numExecutedPaths      = -1;
        $this->numClasses            = -1;
        $this->numTestedClasses      = -1;
        $this->numMethods            = -1;
        $this->numTestedMethods      = -1;
    }
}
