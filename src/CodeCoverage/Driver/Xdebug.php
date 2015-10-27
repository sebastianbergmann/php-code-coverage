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
 * Driver for Xdebug's code coverage functionality.
 *
 * @since Class available since Release 1.0.0
 * @codeCoverageIgnore
 */
class PHP_CodeCoverage_Driver_Xdebug implements PHP_CodeCoverage_Driver
{
    /**
     * @var int
     */
    private $flags;

    /**
     * @param bool $pathCoverage
     */
    public function __construct($pathCoverage = true)
    {
        if (!extension_loaded('xdebug') ||
            version_compare(phpversion('xdebug'), '2.3.2', '<')) {
            throw new PHP_CodeCoverage_RuntimeException(
                'This driver requires Xdebug 2.3.2 (or newer)'
            );
        }

        if (!ini_get('xdebug.coverage_enable')) {
            throw new PHP_CodeCoverage_RuntimeException(
                'xdebug.coverage_enable=On has to be set in php.ini'
            );
        }

        $this->flags = XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE;

        if ($pathCoverage) {
            $this->flags |= XDEBUG_CC_BRANCH_CHECK;
        }
    }

    /**
     * Start collection of code coverage information.
     */
    public function start()
    {
        xdebug_start_code_coverage($this->flags);
    }

    /**
     * Stop collection of code coverage information.
     *
     * @return array
     */
    public function stop()
    {
        $data = xdebug_get_code_coverage();
        xdebug_stop_code_coverage();

        return $this->cleanup($data);
    }

    /**
     * @param  array $data
     * @return array
     * @since Method available since Release 2.0.0
     */
    private function cleanup(array $data)
    {
        foreach (array_keys($data) as $file) {
            if (!isset($data[$file]['lines'])) {
                $data[$file] = ['lines' => $data[$file]];
            }
            if (!isset($data[$file]['functions'])) {
                $data[$file]['functions'] = [];
            }

            unset($data[$file]['lines'][0]);

            if ($file != 'xdebug://debug-eval' && file_exists($file)) {
                $numLines = $this->getNumberOfLinesInFile($file);

                foreach (array_keys($data[$file]['lines']) as $line) {
                    if ($line > $numLines) {
                        unset($data[$file]['lines'][$line]);
                    }
                }
            }
        }

        return $data;
    }

    /**
     * @param  string $file
     * @return int
     * @since Method available since Release 2.0.0
     */
    private function getNumberOfLinesInFile($file)
    {
        $buffer = file_get_contents($file);
        $lines  = substr_count($buffer, "\n");

        if (substr($buffer, -1) !== "\n") {
            $lines++;
        }

        return $lines;
    }
}
