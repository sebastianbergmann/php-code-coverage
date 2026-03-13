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
use SebastianBergmann\CodeCoverage\Util\Percentage;

/**
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

            foreach ($this->classes as $class) {
                $this->numExecutableLines += $class->numberOfExecutableLines();
            }

            foreach ($this->childNamespaces as $ns) {
                $this->numExecutableLines += $ns->numberOfExecutableLines();
            }
        }

        return $this->numExecutableLines;
    }

    public function numberOfExecutedLines(): int
    {
        if ($this->numExecutedLines === -1) {
            $this->numExecutedLines = 0;

            foreach ($this->classes as $class) {
                $this->numExecutedLines += $class->numberOfExecutedLines();
            }

            foreach ($this->childNamespaces as $ns) {
                $this->numExecutedLines += $ns->numberOfExecutedLines();
            }
        }

        return $this->numExecutedLines;
    }

    public function numberOfExecutableBranches(): int
    {
        if ($this->numExecutableBranches === -1) {
            $this->numExecutableBranches = 0;

            foreach ($this->classes as $class) {
                $this->numExecutableBranches += $class->numberOfExecutableBranches();
            }

            foreach ($this->childNamespaces as $ns) {
                $this->numExecutableBranches += $ns->numberOfExecutableBranches();
            }
        }

        return $this->numExecutableBranches;
    }

    public function numberOfExecutedBranches(): int
    {
        if ($this->numExecutedBranches === -1) {
            $this->numExecutedBranches = 0;

            foreach ($this->classes as $class) {
                $this->numExecutedBranches += $class->numberOfExecutedBranches();
            }

            foreach ($this->childNamespaces as $ns) {
                $this->numExecutedBranches += $ns->numberOfExecutedBranches();
            }
        }

        return $this->numExecutedBranches;
    }

    public function numberOfExecutablePaths(): int
    {
        if ($this->numExecutablePaths === -1) {
            $this->numExecutablePaths = 0;

            foreach ($this->classes as $class) {
                $this->numExecutablePaths += $class->numberOfExecutablePaths();
            }

            foreach ($this->childNamespaces as $ns) {
                $this->numExecutablePaths += $ns->numberOfExecutablePaths();
            }
        }

        return $this->numExecutablePaths;
    }

    public function numberOfExecutedPaths(): int
    {
        if ($this->numExecutedPaths === -1) {
            $this->numExecutedPaths = 0;

            foreach ($this->classes as $class) {
                $this->numExecutedPaths += $class->numberOfExecutedPaths();
            }

            foreach ($this->childNamespaces as $ns) {
                $this->numExecutedPaths += $ns->numberOfExecutedPaths();
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

            foreach ($this->classes as $class) {
                $this->numMethods += $class->numberOfMethods();
            }

            foreach ($this->childNamespaces as $ns) {
                $this->numMethods += $ns->numberOfMethods();
            }
        }

        return $this->numMethods;
    }

    public function numberOfTestedMethods(): int
    {
        if ($this->numTestedMethods === -1) {
            $this->numTestedMethods = 0;

            foreach ($this->classes as $class) {
                $this->numTestedMethods += $class->numberOfTestedMethods();
            }

            foreach ($this->childNamespaces as $ns) {
                $this->numTestedMethods += $ns->numberOfTestedMethods();
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
