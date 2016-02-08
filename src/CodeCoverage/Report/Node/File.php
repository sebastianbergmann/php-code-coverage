<?php
/*
 * This file is part of the PHP_CodeCoverage package.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Represents a file in the code coverage information tree.
 *
 * @since Class available since Release 1.1.0
 */
class PHP_CodeCoverage_Report_Node_File extends PHP_CodeCoverage_Report_Node
{
    /**
     * @var array
     */
    protected $coverageData;

    /**
     * @var array
     */
    protected $testData;

    /**
     * @var int
     */
    protected $numExecutableLines = 0;

    /**
     * @var int
     */
    protected $numExecutedLines = 0;

    /**
     * @var int
     */
    protected $numExecutablePaths = 0;

    /**
     * @var int
     */
    protected $numExecutedPaths = 0;

    /**
     * @var array
     */
    protected $classes = [];

    /**
     * @var array
     */
    protected $traits = [];

    /**
     * @var array
     */
    protected $functions = [];

    /**
     * @var array
     */
    protected $linesOfCode = [];

    /**
     * @var int
     */
    protected $numTestedTraits = 0;

    /**
     * @var int
     */
    protected $numTestedClasses = 0;

    /**
     * @var int
     */
    protected $numMethods = null;

    /**
     * @var int
     */
    protected $numTestedMethods = null;

    /**
     * @var int
     */
    protected $numTestedFunctions = null;

    /**
     * @var array
     */
    protected $startLines = [];

    /**
     * @var array
     */
    protected $endLines = [];

    /**
     * @var bool
     */
    protected $cacheTokens;

    /**
     * Constructor.
     *
     * @param  string                                    $name
     * @param  PHP_CodeCoverage_Report_Node              $parent
     * @param  array                                     $coverageData
     * @param  array                                     $testData
     * @param  bool                                      $cacheTokens
     * @throws PHP_CodeCoverage_InvalidArgumentException
     */
    public function __construct($name, PHP_CodeCoverage_Report_Node $parent, array $coverageData, array $testData, $cacheTokens)
    {
        if (!is_bool($cacheTokens)) {
            throw PHP_CodeCoverage_InvalidArgumentException::create(
                1,
                'boolean'
            );
        }

        parent::__construct($name, $parent);

        $this->coverageData = $coverageData;
        $this->testData     = $testData;
        $this->cacheTokens  = $cacheTokens;

        $this->calculateStatistics();
    }

    /**
     * Returns the number of files in/under this node.
     *
     * @return int
     */
    public function count()
    {
        return 1;
    }

    /**
     * Returns the code coverage data of this node.
     *
     * @return array
     */
    public function getCoverageData()
    {
        return $this->coverageData;
    }

    /**
     * Returns the test data of this node.
     *
     * @return array
     */
    public function getTestData()
    {
        return $this->testData;
    }

    /**
     * Returns the classes of this node.
     *
     * @return array
     */
    public function getClasses()
    {
        return $this->classes;
    }

    /**
     * Returns the traits of this node.
     *
     * @return array
     */
    public function getTraits()
    {
        return $this->traits;
    }

    /**
     * Returns the functions of this node.
     *
     * @return array
     */
    public function getFunctions()
    {
        return $this->functions;
    }

    /**
     * Returns the LOC/CLOC/NCLOC of this node.
     *
     * @return array
     */
    public function getLinesOfCode()
    {
        return $this->linesOfCode;
    }

    /**
     * Returns the number of executable lines.
     *
     * @return int
     */
    public function getNumExecutableLines()
    {
        return $this->numExecutableLines;
    }

    /**
     * Returns the number of executed lines.
     *
     * @return int
     */
    public function getNumExecutedLines()
    {
        return $this->numExecutedLines;
    }

    /**
     * Returns the number of executable paths.
     *
     * @return int
     */
    public function getNumExecutablePaths()
    {
        return $this->numExecutablePaths;
    }

    /**
     * Returns the number of executed paths.
     *
     * @return int
     */
    public function getNumExecutedPaths()
    {
        return $this->numExecutedPaths;
    }

    /**
     * Returns the number of classes.
     *
     * @return int
     */
    public function getNumClasses()
    {
        return count($this->classes);
    }

    /**
     * Returns the number of tested classes.
     *
     * @return int
     */
    public function getNumTestedClasses()
    {
        return $this->numTestedClasses;
    }

    /**
     * Returns the number of traits.
     *
     * @return int
     */
    public function getNumTraits()
    {
        return count($this->traits);
    }

    /**
     * Returns the number of tested traits.
     *
     * @return int
     */
    public function getNumTestedTraits()
    {
        return $this->numTestedTraits;
    }

    /**
     * Returns the number of methods.
     *
     * @return int
     */
    public function getNumMethods()
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
     *
     * @return int
     */
    public function getNumTestedMethods()
    {
        if ($this->numTestedMethods === null) {
            $this->numTestedMethods = 0;

            foreach ($this->classes as $class) {
                foreach ($class['methods'] as $method) {
                    if ($method['executableLines'] > 0 &&
                        $method['coverage'] == 100) {
                        $this->numTestedMethods++;
                    }
                }
            }

            foreach ($this->traits as $trait) {
                foreach ($trait['methods'] as $method) {
                    if ($method['executableLines'] > 0 &&
                        $method['coverage'] == 100) {
                        $this->numTestedMethods++;
                    }
                }
            }
        }

        return $this->numTestedMethods;
    }

    /**
     * Returns the number of functions.
     *
     * @return int
     */
    public function getNumFunctions()
    {
        return count($this->functions);
    }

    /**
     * Returns the number of tested functions.
     *
     * @return int
     */
    public function getNumTestedFunctions()
    {
        if ($this->numTestedFunctions === null) {
            $this->numTestedFunctions = 0;

            foreach ($this->functions as $function) {
                if ($function['executableLines'] > 0 &&
                    $function['coverage'] == 100) {
                    $this->numTestedFunctions++;
                }
            }
        }

        return $this->numTestedFunctions;
    }

    /**
     * Calculates coverage statistics for the file.
     */
    protected function calculateStatistics()
    {
        $classStack = $functionStack = [];

        if ($this->cacheTokens) {
            $tokens = PHP_Token_Stream_CachingFactory::get($this->getPath());
        } else {
            $tokens = new PHP_Token_Stream($this->getPath());
        }

        $this->processClasses($tokens);
        $this->processTraits($tokens);
        $this->processFunctions($tokens);
        $this->linesOfCode = $tokens->getLinesOfCode();
        unset($tokens);

        for ($lineNumber = 1; $lineNumber <= $this->linesOfCode['loc']; $lineNumber++) {
            if (isset($this->startLines[$lineNumber])) {
                // Start line of a class.
                if (isset($this->startLines[$lineNumber]['className'])) {
                    if (isset($currentClass)) {
                        $classStack[] = &$currentClass;
                    }

                    $currentClass = &$this->startLines[$lineNumber];
                } // Start line of a trait.
                elseif (isset($this->startLines[$lineNumber]['traitName'])) {
                    $currentTrait = &$this->startLines[$lineNumber];
                } // Start line of a method.
                elseif (isset($this->startLines[$lineNumber]['methodName'])) {
                    $currentMethod = &$this->startLines[$lineNumber];
                } // Start line of a function.
                elseif (isset($this->startLines[$lineNumber]['functionName'])) {
                    if (isset($currentFunction)) {
                        $functionStack[] = &$currentFunction;
                    }

                    $currentFunction = &$this->startLines[$lineNumber];
                }
            }

            if (isset($this->coverageData['lines'][$lineNumber])) {
                if (isset($currentClass)) {
                    $currentClass['executableLines']++;
                }

                if (isset($currentTrait)) {
                    $currentTrait['executableLines']++;
                }

                if (isset($currentMethod)) {
                    $currentMethod['executableLines']++;
                }

                if (isset($currentFunction)) {
                    $currentFunction['executableLines']++;
                }

                $this->numExecutableLines++;

                if (!empty($this->coverageData['lines'][$lineNumber]['tests'])) {
                    if (isset($currentClass)) {
                        $currentClass['executedLines']++;
                    }

                    if (isset($currentTrait)) {
                        $currentTrait['executedLines']++;
                    }

                    if (isset($currentMethod)) {
                        $currentMethod['executedLines']++;
                    }

                    if (isset($currentFunction)) {
                        $currentFunction['executedLines']++;
                    }

                    $this->numExecutedLines++;
                }
            }

            if (isset($this->endLines[$lineNumber])) {
                // End line of a class.
                if (isset($this->endLines[$lineNumber]['className'])) {
                    unset($currentClass);

                    if ($classStack) {
                        end($classStack);
                        $key          = key($classStack);
                        $currentClass = &$classStack[$key];
                        unset($classStack[$key]);
                    }
                } // End line of a trait.
                elseif (isset($this->endLines[$lineNumber]['traitName'])) {
                    unset($currentTrait);
                } // End line of a method.
                elseif (isset($this->endLines[$lineNumber]['methodName'])) {
                    unset($currentMethod);
                } // End line of a function.
                elseif (isset($this->endLines[$lineNumber]['functionName'])) {
                    unset($currentFunction);

                    if ($functionStack) {
                        end($functionStack);
                        $key             = key($functionStack);
                        $currentFunction = &$functionStack[$key];
                        unset($functionStack[$key]);
                    }
                }
            }
        }

        foreach ($this->functions as &$function) {
            if (isset($this->coverageData['paths'][$function['functionName']])) {
                $functionPaths = $this->coverageData['paths'][$function['functionName']];
                $this->calcPathsAggregate($functionPaths, $numExecutablePaths, $numExecutedPaths);

                $function['executablePaths'] = $numExecutablePaths;
                $this->numExecutablePaths   += $numExecutablePaths;

                $function['executedPaths'] = $numExecutedPaths;
                $this->numExecutedPaths   += $numExecutedPaths;
            }
        }

        foreach ($this->traits as &$trait) {
            $fqcn = $trait['traitName'];
            if (!empty($trait['package']['namespace'])) {
                $fqcn = $trait['package']['namespace'] . '\\' . $fqcn;
            }

            $this->calcAndApplyClassAggregate($trait, $fqcn);
        }

        foreach ($this->classes as &$class) {
            $fqcn = $class['className'];
            if (!empty($class['package']['namespace'])) {
                $fqcn = $class['package']['namespace'] . '\\' . $fqcn;
            }

            $this->calcAndApplyClassAggregate($class, $fqcn);
        }
    }

    /**
     * @param PHP_Token_Stream $tokens
     */
    protected function processClasses(PHP_Token_Stream $tokens)
    {
        $classes = $tokens->getClasses();
        unset($tokens);

        $link = $this->getId() . '.html#';

        foreach ($classes as $className => $class) {
            $this->classes[$className] = [
                'className'       => $className,
                'methods'         => [],
                'startLine'       => $class['startLine'],
                'executableLines' => 0,
                'executedLines'   => 0,
                'executablePaths' => 0,
                'executedPaths'   => 0,
                'ccn'             => 0,
                'coverage'        => 0,
                'crap'            => 0,
                'package'         => $class['package'],
                'link'            => $link . $class['startLine']
            ];

            $this->startLines[$class['startLine']] = &$this->classes[$className];
            $this->endLines[$class['endLine']]     = &$this->classes[$className];

            foreach ($class['methods'] as $methodName => $method) {
                $this->classes[$className]['methods'][$methodName] = $this->newMethod($methodName, $method, $link);

                $this->startLines[$method['startLine']] = &$this->classes[$className]['methods'][$methodName];
                $this->endLines[$method['endLine']]     = &$this->classes[$className]['methods'][$methodName];
            }
        }
    }

    /**
     * @param PHP_Token_Stream $tokens
     */
    protected function processTraits(PHP_Token_Stream $tokens)
    {
        $traits = $tokens->getTraits();
        unset($tokens);

        $link = $this->getId() . '.html#';

        foreach ($traits as $traitName => $trait) {
            $this->traits[$traitName] = [
                'traitName'       => $traitName,
                'methods'         => [],
                'startLine'       => $trait['startLine'],
                'executableLines' => 0,
                'executedLines'   => 0,
                'executablePaths' => 0,
                'executedPaths'   => 0,
                'ccn'             => 0,
                'coverage'        => 0,
                'crap'            => 0,
                'package'         => $trait['package'],
                'link'            => $link . $trait['startLine']
            ];

            $this->startLines[$trait['startLine']] = &$this->traits[$traitName];
            $this->endLines[$trait['endLine']]     = &$this->traits[$traitName];

            foreach ($trait['methods'] as $methodName => $method) {
                $this->traits[$traitName]['methods'][$methodName] = $this->newMethod($methodName, $method, $link);

                $this->startLines[$method['startLine']] = &$this->traits[$traitName]['methods'][$methodName];
                $this->endLines[$method['endLine']]     = &$this->traits[$traitName]['methods'][$methodName];
            }
        }
    }

    /**
     * @param PHP_Token_Stream $tokens
     */
    protected function processFunctions(PHP_Token_Stream $tokens)
    {
        $functions = $tokens->getFunctions();
        unset($tokens);

        $link = $this->getId() . '.html#';

        foreach ($functions as $functionName => $function) {
            $this->functions[$functionName] = [
                'functionName'    => $functionName,
                'signature'       => $function['signature'],
                'startLine'       => $function['startLine'],
                'executableLines' => 0,
                'executedLines'   => 0,
                'executablePaths' => 0,
                'executedPaths'   => 0,
                'ccn'             => $function['ccn'],
                'coverage'        => 0,
                'crap'            => 0,
                'link'            => $link . $function['startLine']
            ];

            $this->startLines[$function['startLine']] = &$this->functions[$functionName];
            $this->endLines[$function['endLine']]     = &$this->functions[$functionName];
        }
    }

    /**
     * Calculates the Change Risk Anti-Patterns (CRAP) index for a unit of code
     * based on its cyclomatic complexity and percentage of code coverage.
     *
     * @param  int    $ccn
     * @param  float  $coverage
     * @return string
     * @since  Method available since Release 1.2.0
     */
    protected function crap($ccn, $coverage)
    {
        if ($coverage == 0) {
            return (string) (pow($ccn, 2) + $ccn);
        }

        if ($coverage >= 95) {
            return (string) $ccn;
        }

        return sprintf(
            '%01.2F',
            pow($ccn, 2) * pow(1 - $coverage/100, 3) + $ccn
        );
    }

    /**
     * @param string $methodName
     * @param array  $method
     * @param string $link
     *
     * @return array
     */
    private function newMethod($methodName, array $method, $link)
    {
        return [
            'methodName'      => $methodName,
            'visibility'      => $method['visibility'],
            'signature'       => $method['signature'],
            'startLine'       => $method['startLine'],
            'endLine'         => $method['endLine'],
            'executableLines' => 0,
            'executedLines'   => 0,
            'executablePaths' => 0,
            'executedPaths'   => 0,
            'ccn'             => $method['ccn'],
            'coverage'        => 0,
            'crap'            => 0,
            'link'            => $link . $method['startLine'],
        ];
    }

    /**
     * @param string $paths
     * @param int    $functionExecutablePaths
     * @param int    $functionExecutedPaths
     */
    private function calcPathsAggregate($paths, &$functionExecutablePaths, &$functionExecutedPaths)
    {
        $functionExecutablePaths = count($paths);
        $functionExecutedPaths   = array_reduce(
            $paths,
            function ($carry, $value) {
                return ($value['hit'] > 0) ? $carry + 1 : $carry;
            },
            0
        );
    }

    /**
     * @param array  $classOrTrait
     * @param string $classOrTraitName
     */
    protected function calcAndApplyClassAggregate(&$classOrTrait, $classOrTraitName)
    {
        foreach ($classOrTrait['methods'] as &$method) {
            if (isset($this->coverageData['branches'])) {
                if ($method['methodName'] === 'anonymous function') {
                    // Locate index
                    $methodCoveragePath = $method['methodName'];
                    foreach ($this->coverageData['branches'] as $index => $branch) {
                        if ($method['startLine'] === $branch[0]['line_start']) {
                            $methodCoveragePath = $index;
                        }
                    }

                } else {
                    $methodCoveragePath = $classOrTraitName . '->' . $method['methodName'];
                }
                if (isset($this->coverageData['paths'][$methodCoveragePath])) {
                    $methodPaths = $this->coverageData['paths'][$methodCoveragePath];
                    $this->calcPathsAggregate($methodPaths, $numExecutablePaths, $numExecutedPaths);

                    $method['executablePaths']        = $numExecutablePaths;
                    $classOrTrait['executablePaths'] += $numExecutablePaths;
                    $this->numExecutablePaths        += $numExecutablePaths;

                    $method['executedPaths']        = $numExecutedPaths;
                    $classOrTrait['executedPaths'] += $numExecutedPaths;
                    $this->numExecutedPaths        += $numExecutedPaths;
                }
            }
            if ($method['executableLines'] > 0) {
                $method['coverage'] = ($method['executedLines'] / $method['executableLines']) * 100;
            } else {
                $method['coverage'] = 100;
            }

            $method['crap'] = $this->crap($method['ccn'], $method['coverage']);

            $classOrTrait['ccn'] += $method['ccn'];
        }

        if ($classOrTrait['executableLines'] > 0) {
            $classOrTrait['coverage'] = ($classOrTrait['executedLines'] / $classOrTrait['executableLines']) * 100;
        } else {
            $classOrTrait['coverage'] = 100;
        }

        if ($classOrTrait['coverage'] == 100) {
            $this->numTestedClasses++;
        }

        $classOrTrait['crap'] = $this->crap($classOrTrait['ccn'], $classOrTrait['coverage']);
    }
}
