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

use function count;
use function explode;
use SebastianBergmann\CodeCoverage\Data\ProcessedClassType;
use SebastianBergmann\CodeCoverage\Data\ProcessedMethodType;
use SebastianBergmann\CodeCoverage\Node\File as FileNode;
use SebastianBergmann\CodeCoverage\Util\Percentage;

/**
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for phpunit/php-code-coverage
 */
final class ClassNode
{
    /**
     * @var non-empty-string
     */
    private readonly string $className;
    private readonly string $namespace;

    /**
     * @var non-empty-string
     */
    private readonly string $filePath;
    private readonly int $startLine;
    private readonly int $endLine;
    private readonly ProcessedClassType $class;
    private readonly FileNode $fileNode;

    /**
     * @var list<TraitSection>
     */
    private readonly array $traitSections;

    /**
     * @var list<ParentSection>
     */
    private readonly array $parentSections;
    private readonly NamespaceNode $parent;
    private ?int $numMethods       = null;
    private ?int $numTestedMethods = null;

    /**
     * @param non-empty-string    $className
     * @param non-empty-string    $filePath
     * @param list<TraitSection>  $traitSections
     * @param list<ParentSection> $parentSections
     */
    public function __construct(string $className, string $namespace, string $filePath, int $startLine, int $endLine, ProcessedClassType $class, FileNode $fileNode, array $traitSections, array $parentSections, NamespaceNode $parent)
    {
        $this->className      = $className;
        $this->namespace      = $namespace;
        $this->filePath       = $filePath;
        $this->startLine      = $startLine;
        $this->endLine        = $endLine;
        $this->class          = $class;
        $this->fileNode       = $fileNode;
        $this->traitSections  = $traitSections;
        $this->parentSections = $parentSections;
        $this->parent         = $parent;
    }

    /**
     * @return non-empty-string
     */
    public function className(): string
    {
        return $this->className;
    }

    public function shortName(): string
    {
        $parts = explode('\\', $this->className);

        return $parts[count($parts) - 1];
    }

    public function namespace(): string
    {
        return $this->namespace;
    }

    /**
     * @return non-empty-string
     */
    public function filePath(): string
    {
        return $this->filePath;
    }

    public function startLine(): int
    {
        return $this->startLine;
    }

    public function endLine(): int
    {
        return $this->endLine;
    }

    public function class_(): ProcessedClassType
    {
        return $this->class;
    }

    public function fileNode(): FileNode
    {
        return $this->fileNode;
    }

    /**
     * @return list<TraitSection>
     */
    public function traitSections(): array
    {
        return $this->traitSections;
    }

    /**
     * @return list<ParentSection>
     */
    public function parentSections(): array
    {
        return $this->parentSections;
    }

    public function parent(): NamespaceNode
    {
        return $this->parent;
    }

    /**
     * @return array<string, ProcessedMethodType>
     */
    public function allMethods(): array
    {
        $methods = $this->class->methods;

        foreach ($this->traitSections as $section) {
            foreach ($section->trait->methods as $name => $method) {
                $methods['[' . $section->traitName . '] ' . $name] = $method;
            }
        }

        foreach ($this->parentSections as $section) {
            foreach ($section->methods as $name => $method) {
                $methods['[' . $section->className . '] ' . $name] = $method;
            }
        }

        return $methods;
    }

    public function numberOfExecutableLines(): int
    {
        $lines = $this->class->executableLines;

        foreach ($this->traitSections as $section) {
            $lines += $section->trait->executableLines;
        }

        foreach ($this->parentSections as $section) {
            foreach ($section->methods as $method) {
                $lines += $method->executableLines;
            }
        }

        return $lines;
    }

    public function numberOfExecutedLines(): int
    {
        $lines = $this->class->executedLines;

        foreach ($this->traitSections as $section) {
            $lines += $section->trait->executedLines;
        }

        foreach ($this->parentSections as $section) {
            foreach ($section->methods as $method) {
                $lines += $method->executedLines;
            }
        }

        return $lines;
    }

    public function numberOfExecutableBranches(): int
    {
        $branches = $this->class->executableBranches;

        foreach ($this->traitSections as $section) {
            $branches += $section->trait->executableBranches;
        }

        foreach ($this->parentSections as $section) {
            foreach ($section->methods as $method) {
                $branches += $method->executableBranches;
            }
        }

        return $branches;
    }

    public function numberOfExecutedBranches(): int
    {
        $branches = $this->class->executedBranches;

        foreach ($this->traitSections as $section) {
            $branches += $section->trait->executedBranches;
        }

        foreach ($this->parentSections as $section) {
            foreach ($section->methods as $method) {
                $branches += $method->executedBranches;
            }
        }

        return $branches;
    }

    public function numberOfExecutablePaths(): int
    {
        $paths = $this->class->executablePaths;

        foreach ($this->traitSections as $section) {
            $paths += $section->trait->executablePaths;
        }

        foreach ($this->parentSections as $section) {
            foreach ($section->methods as $method) {
                $paths += $method->executablePaths;
            }
        }

        return $paths;
    }

    public function numberOfExecutedPaths(): int
    {
        $paths = $this->class->executedPaths;

        foreach ($this->traitSections as $section) {
            $paths += $section->trait->executedPaths;
        }

        foreach ($this->parentSections as $section) {
            foreach ($section->methods as $method) {
                $paths += $method->executedPaths;
            }
        }

        return $paths;
    }

    public function numberOfMethods(): int
    {
        if ($this->numMethods === null) {
            $this->numMethods = 0;

            foreach ($this->allMethods() as $method) {
                if ($method->executableLines > 0) {
                    $this->numMethods++;
                }
            }
        }

        return $this->numMethods;
    }

    public function numberOfTestedMethods(): int
    {
        if ($this->numTestedMethods === null) {
            $this->numTestedMethods = 0;

            foreach ($this->allMethods() as $method) {
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
        if ($this->numberOfMethods() === 0) {
            return Percentage::fromFractionAndTotal(0, 0);
        }

        return Percentage::fromFractionAndTotal(
            $this->numberOfTestedMethods() === $this->numberOfMethods() ? 1 : 0,
            1,
        );
    }
}
