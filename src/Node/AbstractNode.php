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

use SebastianBergmann\CodeCoverage\Percentage;

/**
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 */
abstract class AbstractNode implements \Countable
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $path;

    /**
     * @var array
     */
    private $pathArray;

    /**
     * @var AbstractNode
     */
    private $parent;

    /**
     * @var string
     */
    private $id;

    public function __construct(string $name, self $parent = null)
    {
        if (\substr($name, -1) === \DIRECTORY_SEPARATOR) {
            $name = \substr($name, 0, -1);
        }

        $this->name   = $name;
        $this->parent = $parent;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getId(): string
    {
        if ($this->id === null) {
            $parent = $this->getParent();

            if ($parent === null) {
                $this->id = 'index';
            } else {
                $parentId = $parent->getId();

                if ($parentId === 'index') {
                    $this->id = \str_replace(':', '_', $this->name);
                } else {
                    $this->id = $parentId . '/' . $this->name;
                }
            }
        }

        return $this->id;
    }

    public function getPath(): string
    {
        if ($this->path === null) {
            if ($this->parent === null || $this->parent->getPath() === null || $this->parent->getPath() === false) {
                $this->path = $this->name;
            } else {
                $this->path = $this->parent->getPath() . \DIRECTORY_SEPARATOR . $this->name;
            }
        }

        return $this->path;
    }

    public function getPathAsArray(): array
    {
        if ($this->pathArray === null) {
            if ($this->parent === null) {
                $this->pathArray = [];
            } else {
                $this->pathArray = $this->parent->getPathAsArray();
            }

            $this->pathArray[] = $this;
        }

        return $this->pathArray;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function getTestedClassesPercent(): Percentage
    {
        return Percentage::fromFractionAndTotal(
            $this->getNumTestedClasses(),
            $this->getNumClasses(),
        );
    }

    public function getTestedTraitsPercent(): Percentage
    {
        return Percentage::fromFractionAndTotal(
            $this->getNumTestedTraits(),
            $this->getNumTraits(),
        );
    }

    public function getTestedClassesAndTraitsPercent(): Percentage
    {
        return Percentage::fromFractionAndTotal(
            $this->getNumTestedClassesAndTraits(),
            $this->getNumClassesAndTraits(),
        );
    }

    public function getTestedFunctionsPercent(): Percentage
    {
        return Percentage::fromFractionAndTotal(
            $this->getNumTestedFunctions(),
            $this->getNumFunctions(),
        );
    }

    public function getTestedMethodsPercent(): Percentage
    {
        return Percentage::fromFractionAndTotal(
            $this->getNumTestedMethods(),
            $this->getNumMethods(),
        );
    }

    public function getTestedFunctionsAndMethodsPercent(): Percentage
    {
        return Percentage::fromFractionAndTotal(
            $this->getNumTestedFunctionsAndMethods(),
            $this->getNumFunctionsAndMethods(),
        );
    }

    public function getLineExecutedPercent(): Percentage
    {
        return Percentage::fromFractionAndTotal(
            $this->getNumExecutedLines(),
            $this->getNumExecutableLines(),
        );
    }

    public function getBranchExecutedPercent(): Percentage
    {
        return Percentage::fromFractionAndTotal(
            $this->getNumExecutedBranches(),
            $this->getNumExecutableBranches()
        );
    }

    public function getPathExecutedPercent(): Percentage
    {
        return Percentage::fromFractionAndTotal(
            $this->getNumExecutedPaths(),
            $this->getNumExecutablePaths()
        );
    }

    /**
     * Returns the number of classes and traits.
     */
    public function getNumClassesAndTraits(): int
    {
        return $this->getNumClasses() + $this->getNumTraits();
    }

    /**
     * Returns the number of tested classes and traits.
     */
    public function getNumTestedClassesAndTraits(): int
    {
        return $this->getNumTestedClasses() + $this->getNumTestedTraits();
    }

    /**
     * Returns the classes and traits of this node.
     */
    public function getClassesAndTraits(): array
    {
        return \array_merge($this->getClasses(), $this->getTraits());
    }

    /**
     * Returns the number of functions and methods.
     */
    public function getNumFunctionsAndMethods(): int
    {
        return $this->getNumFunctions() + $this->getNumMethods();
    }

    /**
     * Returns the number of tested functions and methods.
     */
    public function getNumTestedFunctionsAndMethods(): int
    {
        return $this->getNumTestedFunctions() + $this->getNumTestedMethods();
    }

    /**
     * Returns the functions and methods of this node.
     */
    public function getFunctionsAndMethods(): array
    {
        return \array_merge($this->getFunctions(), $this->getMethods());
    }

    /**
     * Returns the classes of this node.
     */
    abstract public function getClasses(): array;

    /**
     * Returns the traits of this node.
     */
    abstract public function getTraits(): array;

    /**
     * Returns the functions of this node.
     */
    abstract public function getFunctions(): array;

    /**
     * Returns the LOC/CLOC/NCLOC of this node.
     */
    abstract public function getLinesOfCode(): array;

    /**
     * Returns the number of executable lines.
     */
    abstract public function getNumExecutableLines(): int;

    /**
     * Returns the number of executed lines.
     */
    abstract public function getNumExecutedLines(): int;

    /**
     * Returns the number of executable branches.
     */
    abstract public function getNumExecutableBranches(): int;

    /**
     * Returns the number of executed branches.
     */
    abstract public function getNumExecutedBranches(): int;

    /**
     * Returns the number of executable paths.
     */
    abstract public function getNumExecutablePaths(): int;

    /**
     * Returns the number of executed paths.
     */
    abstract public function getNumExecutedPaths(): int;

    /**
     * Returns the number of classes.
     */
    abstract public function getNumClasses(): int;

    /**
     * Returns the number of tested classes.
     */
    abstract public function getNumTestedClasses(): int;

    /**
     * Returns the number of traits.
     */
    abstract public function getNumTraits(): int;

    /**
     * Returns the number of tested traits.
     */
    abstract public function getNumTestedTraits(): int;

    /**
     * Returns the number of methods.
     */
    abstract public function getNumMethods(): int;

    /**
     * Returns the number of tested methods.
     */
    abstract public function getNumTestedMethods(): int;

    /**
     * Returns the number of functions.
     */
    abstract public function getNumFunctions(): int;

    /**
     * Returns the number of tested functions.
     */
    abstract public function getNumTestedFunctions(): int;
}
