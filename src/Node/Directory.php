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

/**
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 */
final class Directory extends AbstractNode implements \IteratorAggregate
{
    /**
     * @var AbstractNode[]
     */
    private $children = [];

    /**
     * @var Directory[]
     */
    private $directories = [];

    /**
     * @var File[]
     */
    private $files = [];

    /**
     * @var array
     */
    private $classes;

    /**
     * @var array
     */
    private $traits;

    /**
     * @var array
     */
    private $functions;

    /**
     * @var array
     */
    private $linesOfCode;

    /**
     * @var int
     */
    private $numFiles = -1;

    /**
     * @var int
     */
    private $numExecutableLines = -1;

    /**
     * @var int
     */
    private $numExecutedLines = -1;

    /**
     * @var int
     */
    private $numExecutableBranches = -1;

    /**
     * @var int
     */
    private $numExecutedBranches = -1;

    /**
     * @var int
     */
    private $numExecutablePaths = -1;

    /**
     * @var int
     */
    private $numExecutedPaths = -1;

    /**
     * @var int
     */
    private $numClasses = -1;

    /**
     * @var int
     */
    private $numTestedClasses = -1;

    /**
     * @var int
     */
    private $numTraits = -1;

    /**
     * @var int
     */
    private $numTestedTraits = -1;

    /**
     * @var int
     */
    private $numMethods = -1;

    /**
     * @var int
     */
    private $numTestedMethods = -1;

    /**
     * @var int
     */
    private $numFunctions = -1;

    /**
     * @var int
     */
    private $numTestedFunctions = -1;

    /**
     * Returns the number of files in/under this node.
     */
    public function count(): int
    {
        if ($this->numFiles === -1) {
            $this->numFiles = 0;

            foreach ($this->children as $child) {
                $this->numFiles += \count($child);
            }
        }

        return $this->numFiles;
    }

    /**
     * Returns an iterator for this node.
     */
    public function getIterator(): \RecursiveIteratorIterator
    {
        return new \RecursiveIteratorIterator(
            new Iterator($this),
            \RecursiveIteratorIterator::SELF_FIRST
        );
    }

    /**
     * Adds a new directory.
     */
    public function addDirectory(string $name): self
    {
        $directory = new self($name, $this);

        $this->children[]    = $directory;
        $this->directories[] = &$this->children[\count($this->children) - 1];

        return $directory;
    }

    /**
     * Adds a new file.
     */
    public function addFile(string $name, array $lineCoverageData, array $functionCoverageData, array $testData, bool $cacheTokens): File
    {
        $file = new File($name, $this, $lineCoverageData, $functionCoverageData, $testData, $cacheTokens);

        $this->children[] = $file;
        $this->files[]    = &$this->children[\count($this->children) - 1];

        $this->numExecutableLines = -1;
        $this->numExecutedLines   = -1;

        return $file;
    }

    /**
     * Returns the directories in this directory.
     */
    public function getDirectories(): array
    {
        return $this->directories;
    }

    /**
     * Returns the files in this directory.
     */
    public function getFiles(): array
    {
        return $this->files;
    }

    /**
     * Returns the child nodes of this node.
     */
    public function getChildNodes(): array
    {
        return $this->children;
    }

    /**
     * Returns the classes of this node.
     */
    public function getClasses(): array
    {
        if ($this->classes === null) {
            $this->classes = [];

            foreach ($this->children as $child) {
                $this->classes = \array_merge(
                    $this->classes,
                    $child->getClasses()
                );
            }
        }

        return $this->classes;
    }

    /**
     * Returns the traits of this node.
     */
    public function getTraits(): array
    {
        if ($this->traits === null) {
            $this->traits = [];

            foreach ($this->children as $child) {
                $this->traits = \array_merge(
                    $this->traits,
                    $child->getTraits()
                );
            }
        }

        return $this->traits;
    }

    /**
     * Returns the functions of this node.
     */
    public function getFunctions(): array
    {
        if ($this->functions === null) {
            $this->functions = [];

            foreach ($this->children as $child) {
                $this->functions = \array_merge(
                    $this->functions,
                    $child->getFunctions()
                );
            }
        }

        return $this->functions;
    }

    /**
     * Returns the LOC/CLOC/NCLOC of this node.
     */
    public function getLinesOfCode(): array
    {
        if ($this->linesOfCode === null) {
            $this->linesOfCode = ['loc' => 0, 'cloc' => 0, 'ncloc' => 0];

            foreach ($this->children as $child) {
                $linesOfCode = $child->getLinesOfCode();

                $this->linesOfCode['loc'] += $linesOfCode['loc'];
                $this->linesOfCode['cloc'] += $linesOfCode['cloc'];
                $this->linesOfCode['ncloc'] += $linesOfCode['ncloc'];
            }
        }

        return $this->linesOfCode;
    }

    /**
     * Returns the number of executable lines.
     */
    public function getNumExecutableLines(): int
    {
        if ($this->numExecutableLines === -1) {
            $this->numExecutableLines = 0;

            foreach ($this->children as $child) {
                $this->numExecutableLines += $child->getNumExecutableLines();
            }
        }

        return $this->numExecutableLines;
    }

    /**
     * Returns the number of executed lines.
     */
    public function getNumExecutedLines(): int
    {
        if ($this->numExecutedLines === -1) {
            $this->numExecutedLines = 0;

            foreach ($this->children as $child) {
                $this->numExecutedLines += $child->getNumExecutedLines();
            }
        }

        return $this->numExecutedLines;
    }

    /**
     * Returns the number of executable branches.
     */
    public function getNumExecutableBranches(): int
    {
        if ($this->numExecutableBranches === -1) {
            $this->numExecutableBranches = 0;

            foreach ($this->children as $child) {
                $this->numExecutableBranches += $child->getNumExecutableBranches();
            }
        }

        return $this->numExecutableBranches;
    }

    /**
     * Returns the number of executed branches.
     */
    public function getNumExecutedBranches(): int
    {
        if ($this->numExecutedBranches === -1) {
            $this->numExecutedBranches = 0;

            foreach ($this->children as $child) {
                $this->numExecutedBranches += $child->getNumExecutedBranches();
            }
        }

        return $this->numExecutedBranches;
    }

    /**
     * Returns the number of executable paths.
     */
    public function getNumExecutablePaths(): int
    {
        if ($this->numExecutablePaths === -1) {
            $this->numExecutablePaths = 0;

            foreach ($this->children as $child) {
                $this->numExecutablePaths += $child->getNumExecutablePaths();
            }
        }

        return $this->numExecutablePaths;
    }

    /**
     * Returns the number of executed paths.
     */
    public function getNumExecutedPaths(): int
    {
        if ($this->numExecutedPaths === -1) {
            $this->numExecutedPaths = 0;

            foreach ($this->children as $child) {
                $this->numExecutedPaths += $child->getNumExecutedPaths();
            }
        }

        return $this->numExecutedPaths;
    }

    /**
     * Returns the number of classes.
     */
    public function getNumClasses(): int
    {
        if ($this->numClasses === -1) {
            $this->numClasses = 0;

            foreach ($this->children as $child) {
                $this->numClasses += $child->getNumClasses();
            }
        }

        return $this->numClasses;
    }

    /**
     * Returns the number of tested classes.
     */
    public function getNumTestedClasses(): int
    {
        if ($this->numTestedClasses === -1) {
            $this->numTestedClasses = 0;

            foreach ($this->children as $child) {
                $this->numTestedClasses += $child->getNumTestedClasses();
            }
        }

        return $this->numTestedClasses;
    }

    /**
     * Returns the number of traits.
     */
    public function getNumTraits(): int
    {
        if ($this->numTraits === -1) {
            $this->numTraits = 0;

            foreach ($this->children as $child) {
                $this->numTraits += $child->getNumTraits();
            }
        }

        return $this->numTraits;
    }

    /**
     * Returns the number of tested traits.
     */
    public function getNumTestedTraits(): int
    {
        if ($this->numTestedTraits === -1) {
            $this->numTestedTraits = 0;

            foreach ($this->children as $child) {
                $this->numTestedTraits += $child->getNumTestedTraits();
            }
        }

        return $this->numTestedTraits;
    }

    /**
     * Returns the number of methods.
     */
    public function getNumMethods(): int
    {
        if ($this->numMethods === -1) {
            $this->numMethods = 0;

            foreach ($this->children as $child) {
                $this->numMethods += $child->getNumMethods();
            }
        }

        return $this->numMethods;
    }

    /**
     * Returns the number of tested methods.
     */
    public function getNumTestedMethods(): int
    {
        if ($this->numTestedMethods === -1) {
            $this->numTestedMethods = 0;

            foreach ($this->children as $child) {
                $this->numTestedMethods += $child->getNumTestedMethods();
            }
        }

        return $this->numTestedMethods;
    }

    /**
     * Returns the number of functions.
     */
    public function getNumFunctions(): int
    {
        if ($this->numFunctions === -1) {
            $this->numFunctions = 0;

            foreach ($this->children as $child) {
                $this->numFunctions += $child->getNumFunctions();
            }
        }

        return $this->numFunctions;
    }

    /**
     * Returns the number of tested functions.
     */
    public function getNumTestedFunctions(): int
    {
        if ($this->numTestedFunctions === -1) {
            $this->numTestedFunctions = 0;

            foreach ($this->children as $child) {
                $this->numTestedFunctions += $child->getNumTestedFunctions();
            }
        }

        return $this->numTestedFunctions;
    }
}
