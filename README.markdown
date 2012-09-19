PHP_CodeCoverage
================

**PHP_CodeCoverage** is a library that provides collection, processing, and rendering functionality for PHP code coverage information.

Requirements
------------

* PHP_CodeCoverage 1.2 requires PHP 5.3.3 (or later) but PHP 5.4.7 (or later) is highly recommended.
* [Xdebug](http://xdebug.org/) 2.0.5 (or later) is required but Xdebug 2.2.1 (or later) is highly recommended.

Installation
------------

PHP_CodeCoverage should be installed using the PEAR Installer, the backbone of the [PHP Extension and Application Repository](http://pear.php.net/) that provides a distribution system for PHP packages.

Depending on your OS distribution and/or your PHP environment, you may need to install PEAR or update your existing PEAR installation before you can proceed with the following instructions. `sudo pear upgrade PEAR` usually suffices to upgrade an existing PEAR installation. The [PEAR Manual ](http://pear.php.net/manual/en/installation.getting.php) explains how to perform a fresh installation of PEAR.

The following two commands (which you may have to run as `root`) are all that is required to install PHP_CodeCoverage using the PEAR Installer:

    pear config-set auto_discover 1
    pear install pear.phpunit.de/PHP_CodeCoverage

After the installation you can find the PHP_CodeCoverage source files inside your local PEAR directory; the path is usually `/usr/lib/php/PHP/CodeCoverage`.

Using the PHP_CodeCoverage API
------------------------------

    <?php
    require 'PHP/CodeCoverage/Autoload.php';

    $coverage = new PHP_CodeCoverage;
    $coverage->start('<name of test>');

    // ...

    $coverage->stop();

    $writer = new PHP_CodeCoverage_Report_Clover;
    $writer->process($coverage, '/tmp/clover.xml');

    $writer = new PHP_CodeCoverage_Report_HTML;
    $writer->process($coverage, '/tmp/code-coverage-report');
