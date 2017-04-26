<?php
/*
 * This file is part of the php-code-coverage package.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SebastianBergmann\CodeCoverage\Handler;

use SebastianBergmann\CodeCoverage\RuntimeException;

/**
 * Data handler for interacting with a Sqlite3 database.
 *
 * @codeCoverageIgnore
 */
class Sqlite3Data
{

    /**
     * The database object.
     *
     * @var \SQLite3
     */
    public $database;

    /**
     * Constructor.
     *
     * @param string $fileName
     *   The name of the file where we're going to store our Sqlite3 data into.
     *
     * @throws RuntimeException
     *   When the file was not created.
     */
    public function __construct($fileName)
    {
        $this->fileName = $fileName;
        $this->database = new \SQLite3($this->fileName);

        if (empty($this->database)) {
            throw new RuntimeException('Could not create SQLite DB '.$this->fileName);
        }
    }

    /**
     * Creates the Sqlite3 database schema.
     */
    public function createSchema()
    {
        $this->database->query('CREATE TABLE coverage (name text, coverage text)');
    }

    /**
     * Writes the xdebug coverage data to the database.
     *
     * @param array $coverage
     *   The array which is returned by xdebug.
     *
     * @see xdebug_get_code_coverage()
     */
    public function write(array $coverage)
    {
        foreach ($coverage as $file => $lines) {
            if (strpos($file, 'xdebug://debug-eval') !== 0 && file_exists($file)) {
                $coverageString = serialize($lines);
                $sql = "INSERT INTO coverage (name, coverage) VALUES ('$file', '$coverageString')";

                try {
                    $this->database->query($sql);
                } catch (\Exception $e) {
                    throw new RuntimeException("Unable to write to the Sqlite3 database. Does PHP have write permissions?");
                }
            }
        }
    }

    /**
     * Retrieve data from the database.
     *
     * @return array
     *   An array containing the coverage data in the same format as it is returned from xdebug.
     *
     * @see xdebug_get_code_coverage()
     */
    public function read()
    {
        $coverage = array_flip($this->getFilenames());

        foreach (array_keys($coverage) as $file) {
            $coverage[$file] = $this->readFile($file);
        }

        return $coverage;
    }

    /**
     * Retrieves the file names from the database.
     *
     * @return array
     *   An array containing all the file names.
     */
    private function getFileNames()
    {
        $fileNames = array();
        $cursor    = $this->database->query('SELECT DISTINCT name FROM coverage');
        while ($row = $cursor->fetchArray()) {
            $fileNames[] = $row[0];
        }

        return $fileNames;
    }

    /**
     * Get the coverage data for one specific file.
     *
     * @param string $file
     *   The absolute path of the file.
     *
     * @return array
     *   An array containing the coverage data of the specified file, in the same format as it is returned from xdebug.
     *
     * @see xdebug_get_code_coverage()
     */
    private function readFile($file)
    {
        $coverage = array();
        $result    = $this->database->query("SELECT coverage FROM coverage WHERE name = '$file'");
        while ($row = $result->fetchArray()) {
            $this->aggregateCoverage($coverage, unserialize($row[0]));
        }

        return $coverage;
    }

    /**
     * Aggregate coverage data.
     *
     * @param array $coverage
     *   The code coverage data.
     * @param array $nextLine
     */
    private function aggregateCoverage(array &$coverage, array $nextLine)
    {
        foreach ($nextLine as $lineNumber => $code) {
            if (!isset($coverage[$lineNumber])) {
                $coverage[$lineNumber] = $code;
            } else {
                $coverage[$lineNumber] = $this->calculateStatusCode($coverage[$lineNumber], $code);
            }
        }
    }

    /**
     * Calculates the status code.
     *
     * @param int $oldCode
     *   The old code.
     * @param int $newCode
     *   The new code.
     *
     * @return int
     *   The updated code.
     */
    private function calculateStatusCode($oldCode, $newCode)
    {
        $updatedValue = 1;
        switch ($oldCode) {
            case -2:
                $updatedValue = -2;
                break;
            case -1:
                $updatedValue = $newCode;
                break;
        }

        return $updatedValue;
    }
}
