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
final class File extends AbstractNode
{
    /**
     * @var array
     */
    private $lineCoverageData;

    /**
     * @var array
     */
    private $functionCoverageData;

    /**
     * @var array
     */
    private $testData;

    /**
     * @var int
     */
    private $numExecutableLines = 0;

    /**
     * @var int
     */
    private $numExecutedLines = 0;

    /**
     * @var int
     */
    private $numExecutableBranches = 0;

    /**
     * @var int
     */
    private $numExecutedBranches = 0;

    /**
     * @var int
     */
    private $numExecutablePaths = 0;

    /**
     * @var int
     */
    private $numExecutedPaths = 0;

    /**
     * @var array
     */
    private $classes = [];

    /**
     * @var array
     */
    private $traits = [];

    /**
     * @var array
     */
    private $functions = [];

    /**
     * @var array
     */
    private $linesOfCode = [];

    /**
     * @var int
     */
    private $numClasses;

    /**
     * @var int
     */
    private $numTestedClasses = 0;

    /**
     * @var int
     */
    private $numTraits;

    /**
     * @var int
     */
    private $numTestedTraits = 0;

    /**
     * @var int
     */
    private $numMethods;

    /**
     * @var int
     */
    private $numTestedMethods;

    /**
     * @var int
     */
    private $numTestedFunctions;

    /**
     * @var bool
     */
    private $cacheTokens;

    /**
     * @var array
     */
    private $codeUnitsByLine = [];

    public function __construct(string $name, AbstractNode $parent, array $lineCoverageData, array $functionCoverageData, array $testData, bool $cacheTokens)
    {
        parent::__construct($name, $parent);

        $this->lineCoverageData     = $lineCoverageData;
        $this->functionCoverageData = $functionCoverageData;
        $this->testData             = $testData;
        $this->cacheTokens          = $cacheTokens;

        $this->calculateStatistics();
    }

    /**
     * Returns the number of files in/under this node.
     */
    public function count(): int
    {
        return 1;
    }

    /**
     * Returns the code coverage data of this node.
     */
    public function getLineCoverageData(): array
    {
        return $this->lineCoverageData;
    }

    /**
     * Returns the code coverage data of this node.
     */
    public function getFunctionCoverageData(): array
    {
        return $this->functionCoverageData;
    }

    /**
     * Returns the test data of this node.
     */
    public function getTestData(): array
    {
        return $this->testData;
    }

    /**
     * Returns the classes of this node.
     */
    public function getClasses(): array
    {
        return $this->classes;
    }

    /**
     * Returns the traits of this node.
     */
    public function getTraits(): array
    {
        return $this->traits;
    }

    /**
     * Returns the functions of this node.
     */
    public function getFunctions(): array
    {
        return $this->functions;
    }

    /**
     * Returns the LOC/CLOC/NCLOC of this node.
     */
    public function getLinesOfCode(): array
    {
        return $this->linesOfCode;
    }

    /**
     * Returns the number of executable lines.
     */
    public function getNumExecutableLines(): int
    {
        return $this->numExecutableLines;
    }

    /**
     * Returns the number of executed lines.
     */
    public function getNumExecutedLines(): int
    {
        return $this->numExecutedLines;
    }

    /**
     * Returns the number of executable branches.
     */
    public function getNumExecutableBranches(): int
    {
        return $this->numExecutableBranches;
    }

    /**
     * Returns the number of executed branches.
     */
    public function getNumExecutedBranches(): int
    {
        return $this->numExecutedBranches;
    }

    /**
     * Returns the number of executable branches.
     */
    public function getNumExecutablePaths(): int
    {
        return $this->numExecutablePaths;
    }

    /**
     * Returns the number of executed branches.
     */
    public function getNumExecutedPaths(): int
    {
        return $this->numExecutedPaths;
    }

    /**
     * Returns the number of classes.
     */
    public function getNumClasses(): int
    {
        if ($this->numClasses === null) {
            $this->numClasses = 0;

            foreach ($this->classes as $class) {
                foreach ($class['methods'] as $method) {
                    if ($method['executableLines'] > 0) {
                        $this->numClasses++;

                        continue 2;
                    }
                }
            }
        }

        return $this->numClasses;
    }

    /**
     * Returns the number of tested classes.
     */
    public function getNumTestedClasses(): int
    {
        return $this->numTestedClasses;
    }

    /**
     * Returns the number of traits.
     */
    public function getNumTraits(): int
    {
        if ($this->numTraits === null) {
            $this->numTraits = 0;

            foreach ($this->traits as $trait) {
                foreach ($trait['methods'] as $method) {
                    if ($method['executableLines'] > 0) {
                        $this->numTraits++;

                        continue 2;
                    }
                }
            }
        }

        return $this->numTraits;
    }

    /**
     * Returns the number of tested traits.
     */
    public function getNumTestedTraits(): int
    {
        return $this->numTestedTraits;
    }

    /**
     * Returns the number of methods.
     */
    public function getNumMethods(): int
    {
        if ($this->numMethods === null) {
            $this->numMethods = 0;

            foreach ($this->classes as $class) {
                foreach ($class['methods'] as $method) {
                    if ($method['executableLines'] > 0) {
                        $this->numMethods++;
                    }
                }
            }

            foreach ($this->traits as $trait) {
                foreach ($trait['methods'] as $method) {
                    if ($method['executableLines'] > 0) {
                        $this->numMethods++;
                    }
                }
            }
        }

        return $this->numMethods;
    }

    /**
     * Returns the number of tested methods.
     */
    public function getNumTestedMethods(): int
    {
        if ($this->numTestedMethods === null) {
            $this->numTestedMethods = 0;

            foreach ($this->classes as $class) {
                foreach ($class['methods'] as $method) {
                    if ($method['executableLines'] > 0 &&
                        $method['coverage'] === 100) {
                        $this->numTestedMethods++;
                    }
                }
            }

            foreach ($this->traits as $trait) {
                foreach ($trait['methods'] as $method) {
                    if ($method['executableLines'] > 0 &&
                        $method['coverage'] === 100) {
                        $this->numTestedMethods++;
                    }
                }
            }
        }

        return $this->numTestedMethods;
    }

    /**
     * Returns the number of functions.
     */
    public function getNumFunctions(): int
    {
        return \count($this->functions);
    }

    /**
     * Returns the number of tested functions.
     */
    public function getNumTestedFunctions(): int
    {
        if ($this->numTestedFunctions === null) {
            $this->numTestedFunctions = 0;

            foreach ($this->functions as $function) {
                if ($function['executableLines'] > 0 &&
                    $function['coverage'] === 100) {
                    $this->numTestedFunctions++;
                }
            }
        }

        return $this->numTestedFunctions;
    }

    private function calculateStatistics(): void
    {
        if ($this->cacheTokens) {
            $tokens = \PHP_Token_Stream_CachingFactory::get($this->getPath());
        } else {
            $tokens = new \PHP_Token_Stream($this->getPath());
        }

        $this->linesOfCode = $tokens->getLinesOfCode();

        foreach (\range(1, $this->linesOfCode['loc']) as $lineNumber) {
            $this->codeUnitsByLine[$lineNumber] = [];
        }

        try {
            $this->processClasses($tokens);
            $this->processTraits($tokens);
            $this->processFunctions($tokens);
        } catch (\OutOfBoundsException $e) {
            // This can happen with PHP_Token_Stream if the file is syntactically invalid,
            // and probably affects a file that wasn't executed.
        }
        unset($tokens);

        foreach (\range(1, $this->linesOfCode['loc']) as $lineNumber) {
            if (isset($this->lineCoverageData[$lineNumber])) {
                foreach ($this->codeUnitsByLine[$lineNumber] as &$codeUnit) {
                    $codeUnit['executableLines']++;
                }

                unset($codeUnit);

                $this->numExecutableLines++;

                if (\count($this->lineCoverageData[$lineNumber]) > 0) {
                    foreach ($this->codeUnitsByLine[$lineNumber] as &$codeUnit) {
                        $codeUnit['executedLines']++;
                    }

                    unset($codeUnit);

                    $this->numExecutedLines++;
                }
            }
        }

        foreach ($this->traits as &$trait) {
            foreach ($trait['methods'] as &$method) {
                if ($method['executableLines'] > 0) {
                    $method['coverage'] = ($method['executedLines'] /
                            $method['executableLines']) * 100;
                } else {
                    $method['coverage'] = 100;
                }

                $method['crap'] = $this->crap(
                    $method['ccn'],
                    $method['coverage']
                );

                $trait['ccn'] += $method['ccn'];
            }

            unset($method);

            if ($trait['executableLines'] > 0) {
                $trait['coverage'] = ($trait['executedLines'] /
                        $trait['executableLines']) * 100;

                if ($trait['coverage'] === 100) {
                    $this->numTestedClasses++;
                }
            } else {
                $trait['coverage'] = 100;
            }

            $trait['crap'] = $this->crap(
                $trait['ccn'],
                $trait['coverage']
            );
        }

        unset($trait);

        foreach ($this->classes as &$class) {
            foreach ($class['methods'] as &$method) {
                if ($method['executableLines'] > 0) {
                    $method['coverage'] = ($method['executedLines'] /
                            $method['executableLines']) * 100;
                } else {
                    $method['coverage'] = 100;
                }

                $method['crap'] = $this->crap(
                    $method['ccn'],
                    $method['coverage']
                );

                $class['ccn'] += $method['ccn'];
            }

            unset($method);

            if ($class['executableLines'] > 0) {
                $class['coverage'] = ($class['executedLines'] /
                        $class['executableLines']) * 100;

                if ($class['coverage'] === 100) {
                    $this->numTestedClasses++;
                }
            } else {
                $class['coverage'] = 100;
            }

            $class['crap'] = $this->crap(
                $class['ccn'],
                $class['coverage']
            );
        }

        unset($class);

        foreach ($this->functions as &$function) {
            if ($function['executableLines'] > 0) {
                $function['coverage'] = ($function['executedLines'] /
                        $function['executableLines']) * 100;
            } else {
                $function['coverage'] = 100;
            }

            if ($function['coverage'] === 100) {
                $this->numTestedFunctions++;
            }

            $function['crap'] = $this->crap(
                $function['ccn'],
                $function['coverage']
            );
        }
    }

    private function processClasses(\PHP_Token_Stream $tokens): void
    {
        $classes = $tokens->getClasses();
        $link    = $this->getId() . '.html#';

        foreach ($classes as $className => $class) {
            if (\strpos($className, 'anonymous') === 0) {
                continue;
            }

            if (!empty($class['package']['namespace'])) {
                $className = $class['package']['namespace'] . '\\' . $className;
            }

            $this->classes[$className] = [
                'className'          => $className,
                'methods'            => [],
                'startLine'          => $class['startLine'],
                'executableLines'    => 0,
                'executedLines'      => 0,
                'executableBranches' => 0,
                'executedBranches'   => 0,
                'executablePaths'    => 0,
                'executedPaths'      => 0,
                'ccn'                => 0,
                'coverage'           => 0,
                'crap'               => 0,
                'package'            => $class['package'],
                'link'               => $link . $class['startLine'],
            ];

            foreach ($class['methods'] as $methodName => $method) {
                if (\strpos($methodName, 'anonymous') === 0) {
                    continue;
                }

                $methodData                                        = $this->newMethod($className, $methodName, $method, $link);
                $this->classes[$className]['methods'][$methodName] = $methodData;

                $this->classes[$className]['executableBranches'] += $methodData['executableBranches'];
                $this->classes[$className]['executedBranches'] += $methodData['executedBranches'];
                $this->classes[$className]['executablePaths'] += $methodData['executablePaths'];
                $this->classes[$className]['executedPaths'] += $methodData['executedPaths'];

                $this->numExecutableBranches += $methodData['executableBranches'];
                $this->numExecutedBranches += $methodData['executedBranches'];
                $this->numExecutablePaths += $methodData['executablePaths'];
                $this->numExecutedPaths += $methodData['executedPaths'];

                foreach (\range($method['startLine'], $method['endLine']) as $lineNumber) {
                    $this->codeUnitsByLine[$lineNumber] = [
                        &$this->classes[$className],
                        &$this->classes[$className]['methods'][$methodName],
                    ];
                }
            }
        }
    }

    private function processTraits(\PHP_Token_Stream $tokens): void
    {
        $traits = $tokens->getTraits();
        $link   = $this->getId() . '.html#';

        foreach ($traits as $traitName => $trait) {
            if (!empty($trait['package']['namespace'])) {
                $traitName = $trait['package']['namespace'] . '\\' . $traitName;
            }

            $this->traits[$traitName] = [
                'traitName'          => $traitName,
                'methods'            => [],
                'startLine'          => $trait['startLine'],
                'executableLines'    => 0,
                'executedLines'      => 0,
                'executableBranches' => 0,
                'executedBranches'   => 0,
                'executablePaths'    => 0,
                'executedPaths'      => 0,
                'ccn'                => 0,
                'coverage'           => 0,
                'crap'               => 0,
                'package'            => $trait['package'],
                'link'               => $link . $trait['startLine'],
            ];

            foreach ($trait['methods'] as $methodName => $method) {
                if (\strpos($methodName, 'anonymous') === 0) {
                    continue;
                }

                $methodData                                       = $this->newMethod($traitName, $methodName, $method, $link);
                $this->traits[$traitName]['methods'][$methodName] = $methodData;

                $this->traits[$traitName]['executableBranches'] += $methodData['executableBranches'];
                $this->traits[$traitName]['executedBranches'] += $methodData['executedBranches'];
                $this->traits[$traitName]['executablePaths'] += $methodData['executablePaths'];
                $this->traits[$traitName]['executedPaths'] += $methodData['executedPaths'];

                foreach (\range($method['startLine'], $method['endLine']) as $lineNumber) {
                    $this->codeUnitsByLine[$lineNumber] = [
                        &$this->traits[$traitName],
                        &$this->traits[$traitName]['methods'][$methodName],
                    ];
                }
            }
        }
    }

    private function processFunctions(\PHP_Token_Stream $tokens): void
    {
        $functions = $tokens->getFunctions();
        $link      = $this->getId() . '.html#';

        foreach ($functions as $functionName => $function) {
            if (\strpos($functionName, 'anonymous') === 0) {
                continue;
            }

            $this->functions[$functionName] = [
                'functionName'       => $functionName,
                'signature'          => $function['signature'],
                'startLine'          => $function['startLine'],
                'executableLines'    => 0,
                'executedLines'      => 0,
                'executableBranches' => 0,
                'executedBranches'   => 0,
                'executablePaths'    => 0,
                'executedPaths'      => 0,
                'ccn'                => $function['ccn'],
                'coverage'           => 0,
                'crap'               => 0,
                'link'               => $link . $function['startLine'],
            ];

            foreach (\range($function['startLine'], $function['endLine']) as $lineNumber) {
                $this->codeUnitsByLine[$lineNumber] = [&$this->functions[$functionName]];
            }
        }
    }

    private function crap(int $ccn, float $coverage): string
    {
        if ($coverage === 0.0) {
            return (string) ($ccn ** 2 + $ccn);
        }

        if ($coverage >= 95) {
            return (string) $ccn;
        }

        return \sprintf(
            '%01.2F',
            $ccn ** 2 * (1 - $coverage / 100) ** 3 + $ccn
        );
    }

    private function newMethod(string $className, string $methodName, array $method, string $link): array
    {
        $methodData = [
            'methodName'         => $methodName,
            'visibility'         => $method['visibility'],
            'signature'          => $method['signature'],
            'startLine'          => $method['startLine'],
            'endLine'            => $method['endLine'],
            'executableLines'    => 0,
            'executedLines'      => 0,
            'executableBranches' => 0,
            'executedBranches'   => 0,
            'executablePaths'    => 0,
            'executedPaths'      => 0,
            'ccn'                => $method['ccn'],
            'coverage'           => 0,
            'crap'               => 0,
            'link'               => $link . $method['startLine'],
        ];

        $key = $className . '->' . $methodName;

        if (isset($this->functionCoverageData[$key]['branches'])) {
            $methodData['executableBranches'] = \count($this->functionCoverageData[$key]['branches']);
            $methodData['executedBranches']   = \count(\array_filter($this->functionCoverageData[$key]['branches'], static function ($branch) {
                return (bool) $branch['hit'];
            }));
        }

        if (isset($this->functionCoverageData[$key]['paths'])) {
            $methodData['executablePaths'] = \count($this->functionCoverageData[$key]['paths']);
            $methodData['executedPaths']   = \count(\array_filter($this->functionCoverageData[$key]['paths'], static function ($path) {
                return (bool) $path['hit'];
            }));
        }

        return $methodData;
    }
}
