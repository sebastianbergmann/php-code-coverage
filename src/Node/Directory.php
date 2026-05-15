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

use function array_merge;
use function assert;
use function count;
use IteratorAggregate;
use RecursiveIteratorIterator;
use SebastianBergmann\CodeCoverage\Data\ProcessedClassType;
use SebastianBergmann\CodeCoverage\Data\ProcessedFunctionType;
use SebastianBergmann\CodeCoverage\Data\ProcessedTraitType;
use SebastianBergmann\CodeCoverage\StaticAnalysis\LinesOfCode;

/**
 * @template-implements IteratorAggregate<int, AbstractNode>
 *
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for phpunit/php-code-coverage
 */
final class Directory extends AbstractNode implements IteratorAggregate
{
    /**
     * @var list<Directory|File>
     */
    private array $children = [];

    /**
     * @var list<Directory>
     */
    private array $directories = [];

    /**
     * @var list<File>
     */
    private array $files = [];

    /**
     * @var ?array<string, ProcessedClassType>
     */
    private ?array $classes = null;

    /**
     * @var ?array<string, ProcessedTraitType>
     */
    private ?array $traits = null;

    /**
     * @var ?array<string, ProcessedFunctionType>
     */
    private ?array $functions                                  = null;
    private ?LinesOfCode $linesOfCode                          = null;
    private int $numFiles                                      = -1;
    private int $numExecutableLines                            = -1;
    private int $numExecutedLines                              = -1;
    private int $numExecutedLinesBySmallTests                  = -1;
    private int $numExecutedLinesByMediumTests                 = -1;
    private int $numExecutedLinesByLargeTests                  = -1;
    private int $numExecutedLinesBySmallOrMediumTests          = -1;
    private int $numExecutedLinesBySmallOrLargeTests           = -1;
    private int $numExecutedLinesByMediumOrLargeTests          = -1;
    private int $numExecutedLinesBySmallOrMediumOrLargeTests   = -1;
    private int $numExecutableBranches                         = -1;
    private int $numExecutedBranches                           = -1;
    private int $numExecutablePaths                            = -1;
    private int $numExecutedPaths                              = -1;
    private int $numFilesWithoutBranchCoverageData             = -1;
    private int $numClasses                                    = -1;
    private int $numTestedClasses                              = -1;
    private int $numTestedClassesBySmallTests                  = -1;
    private int $numTestedClassesByMediumTests                 = -1;
    private int $numTestedClassesByLargeTests                  = -1;
    private int $numTestedClassesBySmallOrMediumTests          = -1;
    private int $numTestedClassesBySmallOrLargeTests           = -1;
    private int $numTestedClassesByMediumOrLargeTests          = -1;
    private int $numTestedClassesBySmallOrMediumOrLargeTests   = -1;
    private int $numTraits                                     = -1;
    private int $numTestedTraits                               = -1;
    private int $numTestedTraitsBySmallTests                   = -1;
    private int $numTestedTraitsByMediumTests                  = -1;
    private int $numTestedTraitsByLargeTests                   = -1;
    private int $numTestedTraitsBySmallOrMediumTests           = -1;
    private int $numTestedTraitsBySmallOrLargeTests            = -1;
    private int $numTestedTraitsByMediumOrLargeTests           = -1;
    private int $numTestedTraitsBySmallOrMediumOrLargeTests    = -1;
    private int $numMethods                                    = -1;
    private int $numTestedMethods                              = -1;
    private int $numTestedMethodsBySmallTests                  = -1;
    private int $numTestedMethodsByMediumTests                 = -1;
    private int $numTestedMethodsByLargeTests                  = -1;
    private int $numTestedMethodsBySmallOrMediumTests          = -1;
    private int $numTestedMethodsBySmallOrLargeTests           = -1;
    private int $numTestedMethodsByMediumOrLargeTests          = -1;
    private int $numTestedMethodsBySmallOrMediumOrLargeTests   = -1;
    private int $numFunctions                                  = -1;
    private int $numTestedFunctions                            = -1;
    private int $numTestedFunctionsBySmallTests                = -1;
    private int $numTestedFunctionsByMediumTests               = -1;
    private int $numTestedFunctionsByLargeTests                = -1;
    private int $numTestedFunctionsBySmallOrMediumTests        = -1;
    private int $numTestedFunctionsBySmallOrLargeTests         = -1;
    private int $numTestedFunctionsByMediumOrLargeTests        = -1;
    private int $numTestedFunctionsBySmallOrMediumOrLargeTests = -1;

    public function count(): int
    {
        if ($this->numFiles === -1) {
            $this->numFiles = 0;

            foreach ($this->children as $child) {
                $this->numFiles += count($child);
            }
        }

        return $this->numFiles;
    }

    /**
     * @return RecursiveIteratorIterator<Iterator<AbstractNode>>
     */
    public function getIterator(): RecursiveIteratorIterator
    {
        return new RecursiveIteratorIterator(
            new Iterator($this),
            RecursiveIteratorIterator::SELF_FIRST,
        );
    }

    public function addDirectory(string $name): self
    {
        $directory = new self($name, $this);

        assert($directory instanceof self);

        $this->children[]    = $directory;
        $this->directories[] = &$this->children[count($this->children) - 1];

        return $directory;
    }

    public function addFile(File $file): void
    {
        $this->children[] = $file;
        $this->files[]    = &$this->children[count($this->children) - 1];

        $this->numExecutableLines                          = -1;
        $this->numExecutedLines                            = -1;
        $this->numExecutedLinesBySmallTests                = -1;
        $this->numExecutedLinesByMediumTests               = -1;
        $this->numExecutedLinesByLargeTests                = -1;
        $this->numExecutedLinesBySmallOrMediumTests        = -1;
        $this->numExecutedLinesBySmallOrLargeTests         = -1;
        $this->numExecutedLinesByMediumOrLargeTests        = -1;
        $this->numExecutedLinesBySmallOrMediumOrLargeTests = -1;
    }

    /**
     * @return list<Directory>
     */
    public function directories(): array
    {
        return $this->directories;
    }

    /**
     * @return list<File>
     */
    public function files(): array
    {
        return $this->files;
    }

    /**
     * @return list<Directory|File>
     */
    public function children(): array
    {
        return $this->children;
    }

    /**
     * @return array<string, ProcessedClassType>
     */
    public function classes(): array
    {
        if ($this->classes === null) {
            $this->classes = [];

            foreach ($this->children as $child) {
                $this->classes = array_merge(
                    $this->classes,
                    $child->classes(),
                );
            }
        }

        return $this->classes;
    }

    /**
     * @return array<string, ProcessedTraitType>
     */
    public function traits(): array
    {
        if ($this->traits === null) {
            $this->traits = [];

            foreach ($this->children as $child) {
                $this->traits = array_merge(
                    $this->traits,
                    $child->traits(),
                );
            }
        }

        return $this->traits;
    }

    /**
     * @return array<string, ProcessedFunctionType>
     */
    public function functions(): array
    {
        if ($this->functions === null) {
            $this->functions = [];

            foreach ($this->children as $child) {
                $this->functions = array_merge(
                    $this->functions,
                    $child->functions(),
                );
            }
        }

        return $this->functions;
    }

    public function linesOfCode(): LinesOfCode
    {
        if ($this->linesOfCode === null) {
            $linesOfCode           = 0;
            $commentLinesOfCode    = 0;
            $nonCommentLinesOfCode = 0;

            foreach ($this->children as $child) {
                $childLinesOfCode = $child->linesOfCode();

                $linesOfCode           += $childLinesOfCode->linesOfCode();
                $commentLinesOfCode    += $childLinesOfCode->commentLinesOfCode();
                $nonCommentLinesOfCode += $childLinesOfCode->nonCommentLinesOfCode();
            }

            $this->linesOfCode = new LinesOfCode($linesOfCode, $commentLinesOfCode, $nonCommentLinesOfCode);
        }

        return $this->linesOfCode;
    }

    public function numberOfExecutableLines(): int
    {
        if ($this->numExecutableLines === -1) {
            $this->numExecutableLines = 0;

            foreach ($this->children as $child) {
                $this->numExecutableLines += $child->numberOfExecutableLines();
            }
        }

        return $this->numExecutableLines;
    }

    public function numberOfExecutedLines(): int
    {
        if ($this->numExecutedLines === -1) {
            $this->numExecutedLines = 0;

            foreach ($this->children as $child) {
                $this->numExecutedLines += $child->numberOfExecutedLines();
            }
        }

        return $this->numExecutedLines;
    }

    public function numberOfExecutedLinesBySmallTests(): int
    {
        if ($this->numExecutedLinesBySmallTests === -1) {
            $this->numExecutedLinesBySmallTests = 0;

            foreach ($this->children as $child) {
                $this->numExecutedLinesBySmallTests += $child->numberOfExecutedLinesBySmallTests();
            }
        }

        return $this->numExecutedLinesBySmallTests;
    }

    public function numberOfExecutedLinesByMediumTests(): int
    {
        if ($this->numExecutedLinesByMediumTests === -1) {
            $this->numExecutedLinesByMediumTests = 0;

            foreach ($this->children as $child) {
                $this->numExecutedLinesByMediumTests += $child->numberOfExecutedLinesByMediumTests();
            }
        }

        return $this->numExecutedLinesByMediumTests;
    }

    public function numberOfExecutedLinesByLargeTests(): int
    {
        if ($this->numExecutedLinesByLargeTests === -1) {
            $this->numExecutedLinesByLargeTests = 0;

            foreach ($this->children as $child) {
                $this->numExecutedLinesByLargeTests += $child->numberOfExecutedLinesByLargeTests();
            }
        }

        return $this->numExecutedLinesByLargeTests;
    }

    public function numberOfExecutedLinesBySmallOrMediumTests(): int
    {
        if ($this->numExecutedLinesBySmallOrMediumTests === -1) {
            $this->numExecutedLinesBySmallOrMediumTests = 0;

            foreach ($this->children as $child) {
                $this->numExecutedLinesBySmallOrMediumTests += $child->numberOfExecutedLinesBySmallOrMediumTests();
            }
        }

        return $this->numExecutedLinesBySmallOrMediumTests;
    }

    public function numberOfExecutedLinesBySmallOrLargeTests(): int
    {
        if ($this->numExecutedLinesBySmallOrLargeTests === -1) {
            $this->numExecutedLinesBySmallOrLargeTests = 0;

            foreach ($this->children as $child) {
                $this->numExecutedLinesBySmallOrLargeTests += $child->numberOfExecutedLinesBySmallOrLargeTests();
            }
        }

        return $this->numExecutedLinesBySmallOrLargeTests;
    }

    public function numberOfExecutedLinesByMediumOrLargeTests(): int
    {
        if ($this->numExecutedLinesByMediumOrLargeTests === -1) {
            $this->numExecutedLinesByMediumOrLargeTests = 0;

            foreach ($this->children as $child) {
                $this->numExecutedLinesByMediumOrLargeTests += $child->numberOfExecutedLinesByMediumOrLargeTests();
            }
        }

        return $this->numExecutedLinesByMediumOrLargeTests;
    }

    public function numberOfExecutedLinesBySmallOrMediumOrLargeTests(): int
    {
        if ($this->numExecutedLinesBySmallOrMediumOrLargeTests === -1) {
            $this->numExecutedLinesBySmallOrMediumOrLargeTests = 0;

            foreach ($this->children as $child) {
                $this->numExecutedLinesBySmallOrMediumOrLargeTests += $child->numberOfExecutedLinesBySmallOrMediumOrLargeTests();
            }
        }

        return $this->numExecutedLinesBySmallOrMediumOrLargeTests;
    }

    public function numberOfExecutableBranches(): int
    {
        if ($this->numExecutableBranches === -1) {
            $this->numExecutableBranches = 0;

            foreach ($this->children as $child) {
                $this->numExecutableBranches += $child->numberOfExecutableBranches();
            }
        }

        return $this->numExecutableBranches;
    }

    public function numberOfExecutedBranches(): int
    {
        if ($this->numExecutedBranches === -1) {
            $this->numExecutedBranches = 0;

            foreach ($this->children as $child) {
                $this->numExecutedBranches += $child->numberOfExecutedBranches();
            }
        }

        return $this->numExecutedBranches;
    }

    public function numberOfExecutablePaths(): int
    {
        if ($this->numExecutablePaths === -1) {
            $this->numExecutablePaths = 0;

            foreach ($this->children as $child) {
                $this->numExecutablePaths += $child->numberOfExecutablePaths();
            }
        }

        return $this->numExecutablePaths;
    }

    public function numberOfExecutedPaths(): int
    {
        if ($this->numExecutedPaths === -1) {
            $this->numExecutedPaths = 0;

            foreach ($this->children as $child) {
                $this->numExecutedPaths += $child->numberOfExecutedPaths();
            }
        }

        return $this->numExecutedPaths;
    }

    public function numberOfFilesWithoutBranchCoverageData(): int
    {
        if ($this->numFilesWithoutBranchCoverageData === -1) {
            $this->numFilesWithoutBranchCoverageData = 0;

            foreach ($this->children as $child) {
                $this->numFilesWithoutBranchCoverageData += $child->numberOfFilesWithoutBranchCoverageData();
            }
        }

        return $this->numFilesWithoutBranchCoverageData;
    }

    public function numberOfClasses(): int
    {
        if ($this->numClasses === -1) {
            $this->numClasses = 0;

            foreach ($this->children as $child) {
                $this->numClasses += $child->numberOfClasses();
            }
        }

        return $this->numClasses;
    }

    public function numberOfTestedClasses(): int
    {
        if ($this->numTestedClasses === -1) {
            $this->numTestedClasses = 0;

            foreach ($this->children as $child) {
                $this->numTestedClasses += $child->numberOfTestedClasses();
            }
        }

        return $this->numTestedClasses;
    }

    public function numberOfTestedClassesBySmallTests(): int
    {
        if ($this->numTestedClassesBySmallTests === -1) {
            $this->numTestedClassesBySmallTests = 0;

            foreach ($this->children as $child) {
                $this->numTestedClassesBySmallTests += $child->numberOfTestedClassesBySmallTests();
            }
        }

        return $this->numTestedClassesBySmallTests;
    }

    public function numberOfTestedClassesByMediumTests(): int
    {
        if ($this->numTestedClassesByMediumTests === -1) {
            $this->numTestedClassesByMediumTests = 0;

            foreach ($this->children as $child) {
                $this->numTestedClassesByMediumTests += $child->numberOfTestedClassesByMediumTests();
            }
        }

        return $this->numTestedClassesByMediumTests;
    }

    public function numberOfTestedClassesByLargeTests(): int
    {
        if ($this->numTestedClassesByLargeTests === -1) {
            $this->numTestedClassesByLargeTests = 0;

            foreach ($this->children as $child) {
                $this->numTestedClassesByLargeTests += $child->numberOfTestedClassesByLargeTests();
            }
        }

        return $this->numTestedClassesByLargeTests;
    }

    public function numberOfTestedClassesBySmallOrMediumTests(): int
    {
        if ($this->numTestedClassesBySmallOrMediumTests === -1) {
            $this->numTestedClassesBySmallOrMediumTests = 0;

            foreach ($this->children as $child) {
                $this->numTestedClassesBySmallOrMediumTests += $child->numberOfTestedClassesBySmallOrMediumTests();
            }
        }

        return $this->numTestedClassesBySmallOrMediumTests;
    }

    public function numberOfTestedClassesBySmallOrLargeTests(): int
    {
        if ($this->numTestedClassesBySmallOrLargeTests === -1) {
            $this->numTestedClassesBySmallOrLargeTests = 0;

            foreach ($this->children as $child) {
                $this->numTestedClassesBySmallOrLargeTests += $child->numberOfTestedClassesBySmallOrLargeTests();
            }
        }

        return $this->numTestedClassesBySmallOrLargeTests;
    }

    public function numberOfTestedClassesByMediumOrLargeTests(): int
    {
        if ($this->numTestedClassesByMediumOrLargeTests === -1) {
            $this->numTestedClassesByMediumOrLargeTests = 0;

            foreach ($this->children as $child) {
                $this->numTestedClassesByMediumOrLargeTests += $child->numberOfTestedClassesByMediumOrLargeTests();
            }
        }

        return $this->numTestedClassesByMediumOrLargeTests;
    }

    public function numberOfTestedClassesBySmallOrMediumOrLargeTests(): int
    {
        if ($this->numTestedClassesBySmallOrMediumOrLargeTests === -1) {
            $this->numTestedClassesBySmallOrMediumOrLargeTests = 0;

            foreach ($this->children as $child) {
                $this->numTestedClassesBySmallOrMediumOrLargeTests += $child->numberOfTestedClassesBySmallOrMediumOrLargeTests();
            }
        }

        return $this->numTestedClassesBySmallOrMediumOrLargeTests;
    }

    public function numberOfTraits(): int
    {
        if ($this->numTraits === -1) {
            $this->numTraits = 0;

            foreach ($this->children as $child) {
                $this->numTraits += $child->numberOfTraits();
            }
        }

        return $this->numTraits;
    }

    public function numberOfTestedTraits(): int
    {
        if ($this->numTestedTraits === -1) {
            $this->numTestedTraits = 0;

            foreach ($this->children as $child) {
                $this->numTestedTraits += $child->numberOfTestedTraits();
            }
        }

        return $this->numTestedTraits;
    }

    public function numberOfTestedTraitsBySmallTests(): int
    {
        if ($this->numTestedTraitsBySmallTests === -1) {
            $this->numTestedTraitsBySmallTests = 0;

            foreach ($this->children as $child) {
                $this->numTestedTraitsBySmallTests += $child->numberOfTestedTraitsBySmallTests();
            }
        }

        return $this->numTestedTraitsBySmallTests;
    }

    public function numberOfTestedTraitsByMediumTests(): int
    {
        if ($this->numTestedTraitsByMediumTests === -1) {
            $this->numTestedTraitsByMediumTests = 0;

            foreach ($this->children as $child) {
                $this->numTestedTraitsByMediumTests += $child->numberOfTestedTraitsByMediumTests();
            }
        }

        return $this->numTestedTraitsByMediumTests;
    }

    public function numberOfTestedTraitsByLargeTests(): int
    {
        if ($this->numTestedTraitsByLargeTests === -1) {
            $this->numTestedTraitsByLargeTests = 0;

            foreach ($this->children as $child) {
                $this->numTestedTraitsByLargeTests += $child->numberOfTestedTraitsByLargeTests();
            }
        }

        return $this->numTestedTraitsByLargeTests;
    }

    public function numberOfTestedTraitsBySmallOrMediumTests(): int
    {
        if ($this->numTestedTraitsBySmallOrMediumTests === -1) {
            $this->numTestedTraitsBySmallOrMediumTests = 0;

            foreach ($this->children as $child) {
                $this->numTestedTraitsBySmallOrMediumTests += $child->numberOfTestedTraitsBySmallOrMediumTests();
            }
        }

        return $this->numTestedTraitsBySmallOrMediumTests;
    }

    public function numberOfTestedTraitsBySmallOrLargeTests(): int
    {
        if ($this->numTestedTraitsBySmallOrLargeTests === -1) {
            $this->numTestedTraitsBySmallOrLargeTests = 0;

            foreach ($this->children as $child) {
                $this->numTestedTraitsBySmallOrLargeTests += $child->numberOfTestedTraitsBySmallOrLargeTests();
            }
        }

        return $this->numTestedTraitsBySmallOrLargeTests;
    }

    public function numberOfTestedTraitsByMediumOrLargeTests(): int
    {
        if ($this->numTestedTraitsByMediumOrLargeTests === -1) {
            $this->numTestedTraitsByMediumOrLargeTests = 0;

            foreach ($this->children as $child) {
                $this->numTestedTraitsByMediumOrLargeTests += $child->numberOfTestedTraitsByMediumOrLargeTests();
            }
        }

        return $this->numTestedTraitsByMediumOrLargeTests;
    }

    public function numberOfTestedTraitsBySmallOrMediumOrLargeTests(): int
    {
        if ($this->numTestedTraitsBySmallOrMediumOrLargeTests === -1) {
            $this->numTestedTraitsBySmallOrMediumOrLargeTests = 0;

            foreach ($this->children as $child) {
                $this->numTestedTraitsBySmallOrMediumOrLargeTests += $child->numberOfTestedTraitsBySmallOrMediumOrLargeTests();
            }
        }

        return $this->numTestedTraitsBySmallOrMediumOrLargeTests;
    }

    public function numberOfMethods(): int
    {
        if ($this->numMethods === -1) {
            $this->numMethods = 0;

            foreach ($this->children as $child) {
                $this->numMethods += $child->numberOfMethods();
            }
        }

        return $this->numMethods;
    }

    public function numberOfTestedMethods(): int
    {
        if ($this->numTestedMethods === -1) {
            $this->numTestedMethods = 0;

            foreach ($this->children as $child) {
                $this->numTestedMethods += $child->numberOfTestedMethods();
            }
        }

        return $this->numTestedMethods;
    }

    public function numberOfTestedMethodsBySmallTests(): int
    {
        if ($this->numTestedMethodsBySmallTests === -1) {
            $this->numTestedMethodsBySmallTests = 0;

            foreach ($this->children as $child) {
                $this->numTestedMethodsBySmallTests += $child->numberOfTestedMethodsBySmallTests();
            }
        }

        return $this->numTestedMethodsBySmallTests;
    }

    public function numberOfTestedMethodsByMediumTests(): int
    {
        if ($this->numTestedMethodsByMediumTests === -1) {
            $this->numTestedMethodsByMediumTests = 0;

            foreach ($this->children as $child) {
                $this->numTestedMethodsByMediumTests += $child->numberOfTestedMethodsByMediumTests();
            }
        }

        return $this->numTestedMethodsByMediumTests;
    }

    public function numberOfTestedMethodsByLargeTests(): int
    {
        if ($this->numTestedMethodsByLargeTests === -1) {
            $this->numTestedMethodsByLargeTests = 0;

            foreach ($this->children as $child) {
                $this->numTestedMethodsByLargeTests += $child->numberOfTestedMethodsByLargeTests();
            }
        }

        return $this->numTestedMethodsByLargeTests;
    }

    public function numberOfTestedMethodsBySmallOrMediumTests(): int
    {
        if ($this->numTestedMethodsBySmallOrMediumTests === -1) {
            $this->numTestedMethodsBySmallOrMediumTests = 0;

            foreach ($this->children as $child) {
                $this->numTestedMethodsBySmallOrMediumTests += $child->numberOfTestedMethodsBySmallOrMediumTests();
            }
        }

        return $this->numTestedMethodsBySmallOrMediumTests;
    }

    public function numberOfTestedMethodsBySmallOrLargeTests(): int
    {
        if ($this->numTestedMethodsBySmallOrLargeTests === -1) {
            $this->numTestedMethodsBySmallOrLargeTests = 0;

            foreach ($this->children as $child) {
                $this->numTestedMethodsBySmallOrLargeTests += $child->numberOfTestedMethodsBySmallOrLargeTests();
            }
        }

        return $this->numTestedMethodsBySmallOrLargeTests;
    }

    public function numberOfTestedMethodsByMediumOrLargeTests(): int
    {
        if ($this->numTestedMethodsByMediumOrLargeTests === -1) {
            $this->numTestedMethodsByMediumOrLargeTests = 0;

            foreach ($this->children as $child) {
                $this->numTestedMethodsByMediumOrLargeTests += $child->numberOfTestedMethodsByMediumOrLargeTests();
            }
        }

        return $this->numTestedMethodsByMediumOrLargeTests;
    }

    public function numberOfTestedMethodsBySmallOrMediumOrLargeTests(): int
    {
        if ($this->numTestedMethodsBySmallOrMediumOrLargeTests === -1) {
            $this->numTestedMethodsBySmallOrMediumOrLargeTests = 0;

            foreach ($this->children as $child) {
                $this->numTestedMethodsBySmallOrMediumOrLargeTests += $child->numberOfTestedMethodsBySmallOrMediumOrLargeTests();
            }
        }

        return $this->numTestedMethodsBySmallOrMediumOrLargeTests;
    }

    public function numberOfFunctions(): int
    {
        if ($this->numFunctions === -1) {
            $this->numFunctions = 0;

            foreach ($this->children as $child) {
                $this->numFunctions += $child->numberOfFunctions();
            }
        }

        return $this->numFunctions;
    }

    public function numberOfTestedFunctions(): int
    {
        if ($this->numTestedFunctions === -1) {
            $this->numTestedFunctions = 0;

            foreach ($this->children as $child) {
                $this->numTestedFunctions += $child->numberOfTestedFunctions();
            }
        }

        return $this->numTestedFunctions;
    }

    public function numberOfTestedFunctionsBySmallTests(): int
    {
        if ($this->numTestedFunctionsBySmallTests === -1) {
            $this->numTestedFunctionsBySmallTests = 0;

            foreach ($this->children as $child) {
                $this->numTestedFunctionsBySmallTests += $child->numberOfTestedFunctionsBySmallTests();
            }
        }

        return $this->numTestedFunctionsBySmallTests;
    }

    public function numberOfTestedFunctionsByMediumTests(): int
    {
        if ($this->numTestedFunctionsByMediumTests === -1) {
            $this->numTestedFunctionsByMediumTests = 0;

            foreach ($this->children as $child) {
                $this->numTestedFunctionsByMediumTests += $child->numberOfTestedFunctionsByMediumTests();
            }
        }

        return $this->numTestedFunctionsByMediumTests;
    }

    public function numberOfTestedFunctionsByLargeTests(): int
    {
        if ($this->numTestedFunctionsByLargeTests === -1) {
            $this->numTestedFunctionsByLargeTests = 0;

            foreach ($this->children as $child) {
                $this->numTestedFunctionsByLargeTests += $child->numberOfTestedFunctionsByLargeTests();
            }
        }

        return $this->numTestedFunctionsByLargeTests;
    }

    public function numberOfTestedFunctionsBySmallOrMediumTests(): int
    {
        if ($this->numTestedFunctionsBySmallOrMediumTests === -1) {
            $this->numTestedFunctionsBySmallOrMediumTests = 0;

            foreach ($this->children as $child) {
                $this->numTestedFunctionsBySmallOrMediumTests += $child->numberOfTestedFunctionsBySmallOrMediumTests();
            }
        }

        return $this->numTestedFunctionsBySmallOrMediumTests;
    }

    public function numberOfTestedFunctionsBySmallOrLargeTests(): int
    {
        if ($this->numTestedFunctionsBySmallOrLargeTests === -1) {
            $this->numTestedFunctionsBySmallOrLargeTests = 0;

            foreach ($this->children as $child) {
                $this->numTestedFunctionsBySmallOrLargeTests += $child->numberOfTestedFunctionsBySmallOrLargeTests();
            }
        }

        return $this->numTestedFunctionsBySmallOrLargeTests;
    }

    public function numberOfTestedFunctionsByMediumOrLargeTests(): int
    {
        if ($this->numTestedFunctionsByMediumOrLargeTests === -1) {
            $this->numTestedFunctionsByMediumOrLargeTests = 0;

            foreach ($this->children as $child) {
                $this->numTestedFunctionsByMediumOrLargeTests += $child->numberOfTestedFunctionsByMediumOrLargeTests();
            }
        }

        return $this->numTestedFunctionsByMediumOrLargeTests;
    }

    public function numberOfTestedFunctionsBySmallOrMediumOrLargeTests(): int
    {
        if ($this->numTestedFunctionsBySmallOrMediumOrLargeTests === -1) {
            $this->numTestedFunctionsBySmallOrMediumOrLargeTests = 0;

            foreach ($this->children as $child) {
                $this->numTestedFunctionsBySmallOrMediumOrLargeTests += $child->numberOfTestedFunctionsBySmallOrMediumOrLargeTests();
            }
        }

        return $this->numTestedFunctionsBySmallOrMediumOrLargeTests;
    }
}
