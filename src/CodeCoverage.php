<?php
/*
 * This file is part of the PHP_CodeCoverage package.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use SebastianBergmann\Environment\Runtime;

/**
 * Provides collection functionality for PHP code coverage information.
 *
 * @since Class available since Release 1.0.0
 */
class PHP_CodeCoverage
{
    /**
     * @var PHP_CodeCoverage_Driver
     */
    private $driver;

    /**
     * @var PHP_CodeCoverage_Filter
     */
    private $filter;

    /**
     * @var bool
     */
    private $cacheTokens = false;

    /**
     * @var bool
     */
    private $checkForUnintentionallyCoveredCode = false;

    /**
     * @var bool
     */
    private $forceCoversAnnotation = false;

    /**
     * @var bool
     */
    private $addUncoveredFilesFromWhitelist = true;

    /**
     * @var bool
     */
    private $processUncoveredFilesFromWhitelist = false;

    /**
     * @var mixed
     */
    private $currentId;

    /**
     * Code coverage data.
     *
     * @var array
     */
    private $data = [];

    /**
     * @var array
     */
    private $ignoredLines = [];

    /**
     * @var bool
     */
    private $disableIgnoredLines = false;

    /**
     * Test data.
     *
     * @var array
     */
    private $tests = [];

    /**
     * @var bool
     */
    private $pathCoverage;

    /**
     * Constructor.
     *
     * @param  PHP_CodeCoverage_Driver                   $driver
     * @param  PHP_CodeCoverage_Filter                   $filter
     * @param  null|bool                                 $pathCoverage `null` enables path coverage if supported.
     * @throws PHP_CodeCoverage_InvalidArgumentException
     */
    public function __construct(PHP_CodeCoverage_Driver $driver = null, PHP_CodeCoverage_Filter $filter = null, $pathCoverage = null)
    {
        if ($pathCoverage === null) {
            $pathCoverage = version_compare(phpversion('xdebug'), '2.3.2', '>=');
        } elseif (!is_bool($pathCoverage)) {
            throw PHP_CodeCoverage_InvalidArgumentException::create(
                3,
                'boolean'
            );
        }

        if ($driver === null) {
            $driver = $this->selectDriver($pathCoverage);
        }

        if ($filter === null) {
            $filter = new PHP_CodeCoverage_Filter;
        }

        $this->driver       = $driver;
        $this->filter       = $filter;
        $this->pathCoverage = $pathCoverage;
    }

    /**
     * Returns the PHP_CodeCoverage_Report_Node_* object graph
     * for this PHP_CodeCoverage object.
     *
     * @return PHP_CodeCoverage_Report_Node_Directory
     * @since  Method available since Release 1.1.0
     */
    public function getReport()
    {
        $factory = new PHP_CodeCoverage_Report_Factory;

        return $factory->create($this);
    }

    /**
     * Clears collected code coverage data.
     */
    public function clear()
    {
        $this->currentId = null;
        $this->data      = [];
        $this->tests     = [];
    }

    /**
     * Returns the PHP_CodeCoverage_Filter used.
     *
     * @return PHP_CodeCoverage_Filter
     */
    public function filter()
    {
        return $this->filter;
    }

    /**
     * Returns the collected code coverage data.
     * Set $raw = true to bypass all filters.
     *
     * @param  bool  $raw
     * @return array
     * @since  Method available since Release 1.1.0
     */
    public function getData($raw = false)
    {
        if (!$raw && $this->addUncoveredFilesFromWhitelist) {
            $this->addUncoveredFilesFromWhitelist();
        }

        return $this->data;
    }

    /**
     * Sets the coverage data.
     *
     * @param array $data
     * @since Method available since Release 2.0.0
     */
    public function setData(array $data)
    {
        $this->data = $data;
    }

    /**
     * Returns the test data.
     *
     * @return array
     * @since  Method available since Release 1.1.0
     */
    public function getTests()
    {
        return $this->tests;
    }

    /**
     * Sets the test data.
     *
     * @param array $tests
     * @since Method available since Release 2.0.0
     */
    public function setTests(array $tests)
    {
        $this->tests = $tests;
    }

    /**
     * Start collection of code coverage information.
     *
     * @param  mixed                                     $id
     * @param  bool                                      $clear
     * @throws PHP_CodeCoverage_InvalidArgumentException
     */
    public function start($id, $clear = false)
    {
        if (!is_bool($clear)) {
            throw PHP_CodeCoverage_InvalidArgumentException::create(
                1,
                'boolean'
            );
        }

        if ($clear) {
            $this->clear();
        }

        $this->currentId = $id;

        $this->driver->start();
    }

    /**
     * Stop collection of code coverage information.
     *
     * @param  bool                                      $append
     * @param  mixed                                     $linesToBeCovered
     * @param  array                                     $linesToBeUsed
     * @return array
     * @throws PHP_CodeCoverage_InvalidArgumentException
     */
    public function stop($append = true, $linesToBeCovered = [], array $linesToBeUsed = [])
    {
        if (!is_bool($append)) {
            throw PHP_CodeCoverage_InvalidArgumentException::create(
                1,
                'boolean'
            );
        }

        if (!is_array($linesToBeCovered) && $linesToBeCovered !== false) {
            throw PHP_CodeCoverage_InvalidArgumentException::create(
                2,
                'array or false'
            );
        }

        $data = $this->driver->stop();
        $this->append($data, null, $append, $linesToBeCovered, $linesToBeUsed);

        $this->currentId = null;

        return $data;
    }

    /**
     * Appends code coverage data.
     *
     * @param  array                             $data
     * @param  mixed                             $id
     * @param  bool                              $append
     * @param  mixed                             $linesToBeCovered
     * @param  array                             $linesToBeUsed
     * @throws PHP_CodeCoverage_RuntimeException
     */
    public function append(array $data, $id = null, $append = true, $linesToBeCovered = [], array $linesToBeUsed = [])
    {
        if ($id === null) {
            $id = $this->currentId;
        }

        if ($id === null) {
            throw new PHP_CodeCoverage_RuntimeException;
        }

        $this->applyListsFilter($data);
        $this->applyIgnoredLinesFilter($data);
        $this->initializeFilesThatAreSeenTheFirstTime($data);

        if (!$append) {
            return;
        }

        if ($id != 'UNCOVERED_FILES_FROM_WHITELIST') {
            $this->applyCoversAnnotationFilter(
                $data,
                $linesToBeCovered,
                $linesToBeUsed
            );
        }

        if (empty($data)) {
            return;
        }

        $size   = 'unknown';
        $status = null;

        if ($id instanceof PHPUnit_Framework_TestCase) {
            $_size = $id->getSize();

            if ($_size == PHPUnit_Util_Test::SMALL) {
                $size = 'small';
            } elseif ($_size == PHPUnit_Util_Test::MEDIUM) {
                $size = 'medium';
            } elseif ($_size == PHPUnit_Util_Test::LARGE) {
                $size = 'large';
            }

            $status = $id->getStatus();
            $id     = get_class($id) . '::' . $id->getName();
        } elseif ($id instanceof PHPUnit_Extensions_PhptTestCase) {
            $size = 'large';
            $id   = $id->getName();
        }

        $this->tests[$id] = ['size' => $size, 'status' => $status];

        foreach ($data as $file => $fileData) {
            if (!$this->filter->isFile($file)) {
                continue;
            }

            foreach ($fileData['lines'] as $function => $functionCoverage) {
                if ($functionCoverage === PHP_CodeCoverage_Driver::LINE_EXECUTED) {
                    $lineData = &$this->data[$file]['lines'][$function];
                    if ($lineData === null) {
                        $lineData = [
                            'pathCovered' => false,
                            'tests'       => [$id],
                        ];
                    } elseif ($this->pathCoverage && !in_array($id, $lineData['tests'])) {
                        $lineData['tests'][] = $id;
                    }
                }
            }

            if ($this->pathCoverage) {
                foreach ($fileData['functions'] as $function => $functionCoverage) {
                    foreach ($functionCoverage['branches'] as $branch => $branchCoverage) {
                        if ($branchCoverage['hit'] === 1) {
                            $this->data[$file]['branches'][$function][$branch]['hit'] = 1;
                            if (!in_array($id, $this->data[$file]['branches'][$function][$branch]['tests'])) {
                                $this->data[$file]['branches'][$function][$branch]['tests'][] = $id;
                            }
                        }
                    }
                    foreach ($functionCoverage['paths'] as $path => $pathCoverage) {
                        if ($pathCoverage['hit'] === 1 && $this->data[$file]['paths'][$function][$path]['hit'] === 0) {
                            $this->data[$file]['paths'][$function][$path]['hit'] = 1;
                        }
                    }
                }
            }
        }
    }

    /**
     * Merges the data from another instance of PHP_CodeCoverage.
     *
     * @param PHP_CodeCoverage $that
     */
    public function merge(PHP_CodeCoverage $that)
    {
        $this->filter->setWhitelistedFiles(
            array_merge($this->filter->getWhitelistedFiles(), $that->filter()->getWhitelistedFiles())
        );

        foreach ($that->getData() as $file => $fileData) {
            if (!isset($this->data[$file])) {
                if (!$that->filter()->isFiltered($file)) {
                    $this->data[$file] = $fileData;
                }

                continue;
            }

            foreach ($fileData['lines'] as $line => $data) {
                if ($data !== null) {
                    if ($this->pathCoverage) {
                        if (!isset($this->data[$file]['lines'][$line])) {
                            $this->data[$file]['lines'][$line] = $data;
                        } else {
                            if ($data['pathCovered']) {
                                $this->data[$file]['lines'][$line]['pathCovered'] = $data['pathCovered'];
                            }
                            $this->data[$file]['lines'][$line]['tests'] = array_unique(
                                array_merge($this->data[$file]['lines'][$line]['tests'], $data['tests'])
                            );
                        }
                    }
                }
            }
        }

        $this->tests = array_merge($this->tests, $that->getTests());

    }

    /**
     * @param  bool                                      $flag
     * @throws PHP_CodeCoverage_InvalidArgumentException
     * @since  Method available since Release 1.1.0
     */
    public function setCacheTokens($flag)
    {
        if (!is_bool($flag)) {
            throw PHP_CodeCoverage_InvalidArgumentException::create(
                1,
                'boolean'
            );
        }

        $this->cacheTokens = $flag;
    }

    /**
     * @since Method available since Release 1.1.0
     */
    public function getCacheTokens()
    {
        return $this->cacheTokens;
    }

    /**
     * @param  bool                                      $flag
     * @throws PHP_CodeCoverage_InvalidArgumentException
     * @since  Method available since Release 2.0.0
     */
    public function setCheckForUnintentionallyCoveredCode($flag)
    {
        if (!is_bool($flag)) {
            throw PHP_CodeCoverage_InvalidArgumentException::create(
                1,
                'boolean'
            );
        }

        $this->checkForUnintentionallyCoveredCode = $flag;
    }

    /**
     * @param  bool                                      $flag
     * @throws PHP_CodeCoverage_InvalidArgumentException
     */
    public function setForceCoversAnnotation($flag)
    {
        if (!is_bool($flag)) {
            throw PHP_CodeCoverage_InvalidArgumentException::create(
                1,
                'boolean'
            );
        }

        $this->forceCoversAnnotation = $flag;
    }

    /**
     * @deprecated
     * @param  bool                                      $flag
     * @throws PHP_CodeCoverage_InvalidArgumentException
     */
    public function setMapTestClassNameToCoveredClassName($flag)
    {
    }

    /**
     * @param  bool                                      $flag
     * @throws PHP_CodeCoverage_InvalidArgumentException
     */
    public function setAddUncoveredFilesFromWhitelist($flag)
    {
        if (!is_bool($flag)) {
            throw PHP_CodeCoverage_InvalidArgumentException::create(
                1,
                'boolean'
            );
        }

        $this->addUncoveredFilesFromWhitelist = $flag;
    }

    /**
     * @param  bool                                      $flag
     * @throws PHP_CodeCoverage_InvalidArgumentException
     */
    public function setProcessUncoveredFilesFromWhitelist($flag)
    {
        if (!is_bool($flag)) {
            throw PHP_CodeCoverage_InvalidArgumentException::create(
                1,
                'boolean'
            );
        }

        $this->processUncoveredFilesFromWhitelist = $flag;
    }

    /**
     * @param  bool                                      $flag
     * @throws PHP_CodeCoverage_InvalidArgumentException
     */
    public function setDisableIgnoredLines($flag)
    {
        if (!is_bool($flag)) {
            throw PHP_CodeCoverage_InvalidArgumentException::create(
                1,
                'boolean'
            );
        }

        $this->disableIgnoredLines = $flag;
    }

    /**
     * Applies the @covers annotation filtering.
     *
     * @param  array                                                $data
     * @param  mixed                                                $linesToBeCovered
     * @param  array                                                $linesToBeUsed
     * @throws PHP_CodeCoverage_UnintentionallyCoveredCodeException
     */
    private function applyCoversAnnotationFilter(array &$data, $linesToBeCovered, array $linesToBeUsed)
    {
        if ($linesToBeCovered === false ||
            ($this->forceCoversAnnotation && empty($linesToBeCovered))) {
            $data = [
                'lines'     => [],
                'functions' => [],
            ];

            return;
        }

        if (empty($linesToBeCovered)) {
            return;
        }

        if ($this->checkForUnintentionallyCoveredCode) {
            $this->performUnintentionallyCoveredCodeCheck(
                $data,
                $linesToBeCovered,
                $linesToBeUsed
            );
        }

        $data = array_intersect_key($data, $linesToBeCovered);

        foreach (array_keys($data) as $filename) {
            $_linesToBeCovered = array_flip($linesToBeCovered[$filename]);

            $data[$filename]['lines'] = array_intersect_key(
                $data[$filename]['lines'],
                $_linesToBeCovered
            );
        }
    }

    /**
     * Applies the whitelist filtering.
     *
     * @param array $data
     */
    private function applyListsFilter(array &$data)
    {
        foreach (array_keys($data) as $filename) {
            if ($this->filter->isFiltered($filename)) {
                unset($data[$filename]);
            }
        }
    }

    /**
     * Applies the "ignored lines" filtering.
     *
     * @param array $data
     */
    private function applyIgnoredLinesFilter(array &$data)
    {
        foreach (array_keys($data) as $filename) {
            if (!$this->filter->isFile($filename)) {
                continue;
            }

            foreach ($this->getLinesToBeIgnored($filename) as $line) {
                unset($data[$filename]['lines'][$line]);
            }
        }
    }

    /**
     * @param array $data
     * @since Method available since Release 1.1.0
     */
    private function initializeFilesThatAreSeenTheFirstTime(array $data)
    {
        foreach ($data as $file => $fileData) {
            if (!$this->filter->isFile($file) || isset($this->data[$file])) {
                continue;
            }

            $this->data[$file] = ['lines' => []];

            foreach ($fileData['lines'] as $lineNumber => $flag) {
                if ($flag === PHP_CodeCoverage_Driver::LINE_NOT_EXECUTABLE) {
                    $this->data[$file]['lines'][$lineNumber] = null;
                } else {
                    $this->data[$file]['lines'][$lineNumber] = [
                        'pathCovered' => false,
                        'tests'       => [],
                    ];
                }
            }

            if ($this->pathCoverage) {
                $this->data[$file]['branches'] = [];
                $this->data[$file]['paths']    = [];

                foreach ($fileData['functions'] as $functionName => $functionData) {
                    $this->data[$file]['branches'][$functionName] = [];
                    $this->data[$file]['paths'][$functionName]    = $functionData['paths'];

                    foreach ($functionData['branches'] as $index => $branch) {
                        $this->data[$file]['branches'][$functionName][$index] = [
                            'hit'        => $branch['hit'],
                            'line_start' => $branch['line_start'],
                            'line_end'   => $branch['line_end'],
                            'tests'      => []
                        ];

                        for ($i = $branch['line_start']; $i < $branch['line_end']; $i++) {
                            if (isset($this->data[$file]['lines'][$i])) {
                                $this->data[$file]['lines'][$i]['pathCovered'] = (bool) $branch['hit'];
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Processes whitelisted files that are not covered.
     */
    private function addUncoveredFilesFromWhitelist()
    {
        $data           = [];
        $uncoveredFiles = array_diff(
            $this->filter->getWhitelist(),
            array_keys($this->data)
        );

        foreach ($uncoveredFiles as $uncoveredFile) {
            if (!file_exists($uncoveredFile)) {
                continue;
            }

            if ($this->processUncoveredFilesFromWhitelist) {
                $this->processUncoveredFileFromWhitelist(
                    $uncoveredFile,
                    $data,
                    $uncoveredFiles
                );
            } else {
                $data[$uncoveredFile] = [
                    'lines'     => [],
                    'functions' => [],
                ];

                $lines = count(file($uncoveredFile));

                for ($i = 1; $i <= $lines; $i++) {
                    $data[$uncoveredFile]['lines'][$i] = PHP_CodeCoverage_Driver::LINE_NOT_EXECUTED;
                }
            }
        }

        $this->append($data, 'UNCOVERED_FILES_FROM_WHITELIST');
    }

    /**
     * @param string $uncoveredFile
     * @param array  $data
     * @param array  $uncoveredFiles
     */
    private function processUncoveredFileFromWhitelist($uncoveredFile, array &$data, array $uncoveredFiles)
    {
        $this->driver->start();
        include_once $uncoveredFile;
        $coverage = $this->driver->stop();

        foreach ($coverage as $file => $fileCoverage) {
            if (!isset($data[$file]) &&
                in_array($file, $uncoveredFiles)) {
                foreach (array_keys($fileCoverage) as $key) {
                    if ($fileCoverage[$key] == PHP_CodeCoverage_Driver::LINE_EXECUTED) {
                        $fileCoverage[$key] = PHP_CodeCoverage_Driver::LINE_NOT_EXECUTED;
                    }
                }

                $data[$file] = $fileCoverage;
            }
        }
    }

    /**
     * Returns the lines of a source file that should be ignored.
     *
     * @param  string                                    $filename
     * @return array
     * @throws PHP_CodeCoverage_InvalidArgumentException
     * @since  Method available since Release 2.0.0
     */
    private function getLinesToBeIgnored($filename)
    {
        if (!is_string($filename)) {
            throw PHP_CodeCoverage_InvalidArgumentException::create(
                1,
                'string'
            );
        }

        if (!isset($this->ignoredLines[$filename])) {
            $this->ignoredLines[$filename] = [];

            if ($this->disableIgnoredLines) {
                return $this->ignoredLines[$filename];
            }

            $ignore   = false;
            $stop     = false;
            $lines    = file($filename);
            $numLines = count($lines);

            foreach ($lines as $index => $line) {
                if (!trim($line)) {
                    $this->ignoredLines[$filename][] = $index + 1;
                }
            }

            if ($this->cacheTokens) {
                $tokens = PHP_Token_Stream_CachingFactory::get($filename);
            } else {
                $tokens = new PHP_Token_Stream($filename);
            }

            $classes = array_merge($tokens->getClasses(), $tokens->getTraits());
            $tokens  = $tokens->tokens();

            foreach ($tokens as $token) {
                switch (get_class($token)) {
                    case 'PHP_Token_COMMENT':
                    case 'PHP_Token_DOC_COMMENT':
                        $_token = trim($token);
                        $_line  = trim($lines[$token->getLine() - 1]);

                        if ($_token == '// @codeCoverageIgnore' ||
                            $_token == '//@codeCoverageIgnore') {
                            $ignore = true;
                            $stop   = true;
                        } elseif ($_token == '// @codeCoverageIgnoreStart' ||
                            $_token == '//@codeCoverageIgnoreStart') {
                            $ignore = true;
                        } elseif ($_token == '// @codeCoverageIgnoreEnd' ||
                            $_token == '//@codeCoverageIgnoreEnd') {
                            $stop = true;
                        }

                        if (!$ignore) {
                            $start = $token->getLine();
                            $end   = $start + substr_count($token, "\n");

                            // Do not ignore the first line when there is a token
                            // before the comment
                            if (0 !== strpos($_token, $_line)) {
                                $start++;
                            }

                            for ($i = $start; $i < $end; $i++) {
                                $this->ignoredLines[$filename][] = $i;
                            }

                            // A DOC_COMMENT token or a COMMENT token starting with "/*"
                            // does not contain the final \n character in its text
                            if (isset($lines[$i-1]) && 0 === strpos($_token, '/*') && '*/' === substr(trim($lines[$i-1]), -2)) {
                                $this->ignoredLines[$filename][] = $i;
                            }
                        }
                        break;

                    case 'PHP_Token_INTERFACE':
                    case 'PHP_Token_TRAIT':
                    case 'PHP_Token_CLASS':
                    case 'PHP_Token_FUNCTION':
                        /* @var PHP_Token_Interface $token */

                        $docblock = $token->getDocblock();

                        $this->ignoredLines[$filename][] = $token->getLine();

                        if (strpos($docblock, '@codeCoverageIgnore') || strpos($docblock, '@deprecated')) {
                            $endLine = $token->getEndLine();

                            for ($i = $token->getLine(); $i <= $endLine; $i++) {
                                $this->ignoredLines[$filename][] = $i;
                            }
                        } elseif ($token instanceof PHP_Token_INTERFACE ||
                            $token instanceof PHP_Token_TRAIT ||
                            $token instanceof PHP_Token_CLASS) {
                            if (empty($classes[$token->getName()]['methods'])) {
                                for ($i = $token->getLine();
                                     $i <= $token->getEndLine();
                                     $i++) {
                                    $this->ignoredLines[$filename][] = $i;
                                }
                            } else {
                                $firstMethod = array_shift(
                                    $classes[$token->getName()]['methods']
                                );

                                do {
                                    $lastMethod = array_pop(
                                        $classes[$token->getName()]['methods']
                                    );
                                } while ($lastMethod !== null &&
                                    substr($lastMethod['signature'], 0, 18) == 'anonymous function');

                                if ($lastMethod === null) {
                                    $lastMethod = $firstMethod;
                                }

                                for ($i = $token->getLine();
                                     $i < $firstMethod['startLine'];
                                     $i++) {
                                    $this->ignoredLines[$filename][] = $i;
                                }

                                for ($i = $token->getEndLine();
                                     $i > $lastMethod['endLine'];
                                     $i--) {
                                    $this->ignoredLines[$filename][] = $i;
                                }
                            }
                        }
                        break;

                    case 'PHP_Token_NAMESPACE':
                        $this->ignoredLines[$filename][] = $token->getEndLine();

                    // Intentional fallthrough
                    case 'PHP_Token_OPEN_TAG':
                    case 'PHP_Token_CLOSE_TAG':
                    case 'PHP_Token_USE':
                        $this->ignoredLines[$filename][] = $token->getLine();
                        break;
                }

                if ($ignore) {
                    $this->ignoredLines[$filename][] = $token->getLine();

                    if ($stop) {
                        $ignore = false;
                        $stop   = false;
                    }
                }
            }

            $this->ignoredLines[$filename][] = $numLines + 1;

            $this->ignoredLines[$filename] = array_unique(
                $this->ignoredLines[$filename]
            );

            sort($this->ignoredLines[$filename]);
        }

        return $this->ignoredLines[$filename];
    }

    /**
     * @param  array                                                $data
     * @param  array                                                $linesToBeCovered
     * @param  array                                                $linesToBeUsed
     * @throws PHP_CodeCoverage_UnintentionallyCoveredCodeException
     * @since Method available since Release 2.0.0
     */
    private function performUnintentionallyCoveredCodeCheck(array &$data, array $linesToBeCovered, array $linesToBeUsed)
    {
        $allowedLines = $this->getAllowedLines(
            $linesToBeCovered,
            $linesToBeUsed
        );

        $message = '';

        foreach ($data as $file => $fileData) {
            foreach ($fileData['lines'] as $line => $flag) {
                if ($flag == 1 &&
                    (!isset($allowedLines[$file]) ||
                        !isset($allowedLines[$file][$line]))) {
                    $message .= sprintf(
                        '- %s:%d' . PHP_EOL,
                        $file,
                        $line
                    );
                }
            }
        }

        if (!empty($message)) {
            throw new PHP_CodeCoverage_UnintentionallyCoveredCodeException(
                $message
            );
        }
    }

    /**
     * @param  array $linesToBeCovered
     * @param  array $linesToBeUsed
     * @return array
     * @since Method available since Release 2.0.0
     */
    private function getAllowedLines(array $linesToBeCovered, array $linesToBeUsed)
    {
        $allowedLines = [];

        foreach (array_keys($linesToBeCovered) as $file) {
            if (!isset($allowedLines[$file])) {
                $allowedLines[$file] = [];
            }

            $allowedLines[$file] = array_merge(
                $allowedLines[$file],
                $linesToBeCovered[$file]
            );
        }

        foreach (array_keys($linesToBeUsed) as $file) {
            if (!isset($allowedLines[$file])) {
                $allowedLines[$file] = [];
            }

            $allowedLines[$file] = array_merge(
                $allowedLines[$file],
                $linesToBeUsed[$file]
            );
        }

        foreach (array_keys($allowedLines) as $file) {
            $allowedLines[$file] = array_flip(
                array_unique($allowedLines[$file])
            );
        }

        return $allowedLines;
    }

    /**
     * @param  bool                              $pathCoverage
     * @return PHP_CodeCoverage_Driver
     * @throws PHP_CodeCoverage_RuntimeException
     */
    private function selectDriver($pathCoverage)
    {
        $runtime = new Runtime;

        if (!$runtime->canCollectCodeCoverage()) {
            throw new PHP_CodeCoverage_RuntimeException('No code coverage driver available');
        }

        if ($runtime->isHHVM()) {
            return new PHP_CodeCoverage_Driver_HHVM;
        } elseif ($runtime->isPHPDBG()) {
            return new PHP_CodeCoverage_Driver_PHPDBG;
        } else {
            return new PHP_CodeCoverage_Driver_Xdebug($pathCoverage);
        }
    }
}
