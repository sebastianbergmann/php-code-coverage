<?php
/*
 * This file is part of the PHP_CodeCoverage package.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author Levi Govaerts <legovaer@me.com>
 */

namespace SebastianBergmann\CodeCoverage\Driver;

use SebastianBergmann\CodeCoverage\RuntimeException;
use SebastianBergmann\CodeCoverage\Handler\Sqlite3Data as DataHandler;

/**
 * Driver for Xdebug's code coverage functionality which saves data to Sqlite3.
 *
 * @since Class available since Release 3.1.0
 * @codeCoverageIgnore
 */
class XdebugSQLite3 extends Xdebug
{

    /**
     * The root folder of the project.
     *
     * @var string
     */
    public $root;

    /**
     *
     */
    public $log;

    /**
     * The name of the database.
     */
    const SQLITE_DB = '/tmp/coverage.sqlite';

    /**
     * @var XdebugSqlite3
     */
    public static $instance;

    /**
     * Stop collection of code coverage information and store it in Sqlite3.
     *
     * @return array
     */
    public function stop()
    {
        $cov = xdebug_get_code_coverage();
        xdebug_stop_code_coverage();

        if (!isset($this->root)) {
            $this->root = getcwd();
        }

        $dataHandler = new DataHandler(self::SQLITE_DB);
        chdir($this->root);
        $dataHandler->write($cov);
        $cleanData = $this->cleanup($dataHandler->read());
        unset($dataHandler); // release sqlite connection

        return $cleanData;
    }

    /**
     * Empties the Sqlite3 database.
     */
    public function resetLog()
    {
        $newFile = fopen(self::SQLITE_DB, 'w');
        if (!$newFile) {
            throw new RuntimeException('Could not create '.self::SQLITE_DB);
        }
        fclose($newFile);
        if (!chmod(self::SQLITE_DB, 0666)) {
            throw new RuntimeException('Could not change ownership on file '.self::SQLITE_DB);
        }
        $handler = new DataHandler(self::SQLITE_DB);
        $handler->createSchema();
    }

    /**
     * Loads the object instance.
     *
     * @return XdebugSqlite3
     *   A new object or the existing object.
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Determines if code coverage is running.
     *
     * @return bool
     *   True if code coverage is running, false if not.
     */
    public static function isCoverageOn()
    {
        if (empty(self::SQLITE_DB) || !file_exists(self::SQLITE_DB)) {
            trigger_error('No coverage log');

            return false;
        }

        return true;
    }
}
