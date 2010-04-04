PHP_CodeCoverage
================

**PHP_CodeCoverage** is a library that provides collection, processing, and rendering functionality for PHP code coverage information.

Installation
------------

PHP_CodeCoverage should be installed using the [PEAR Installer](http://pear.php.net/). This installer is the backbone of PEAR, which provides a distribution system for PHP packages, and is shipped with every release of PHP since version 4.3.0.

The PEAR channel (`pear.phpunit.de`) that is used to distribute PHP_CodeCoverage needs to be registered with the local PEAR environment. Furthermore, a component that PHP_CodeCoverage depends upon is hosted on the eZ Components PEAR channel (`components.ez.no`).

    sb@ubuntu ~ % pear channel-discover pear.phpunit.de
    Adding Channel "pear.phpunit.de" succeeded
    Discovery of channel "pear.phpunit.de" succeeded

    sb@ubuntu ~ % pear channel-discover components.ez.no
    Adding Channel "components.ez.no" succeeded
    Discovery of channel "components.ez.no" succeeded

This has to be done only once. Now the PEAR Installer can be used to install packages from the PHPUnit channel:

    sb@vmware ~ % pear install phpunit/PHP_CodeCoverage
    downloading PHP_CodeCoverage-0.9.0.tgz ...
    Starting to download PHP_CodeCoverage-0.9.0.tgz (108,376 bytes)
    .........................done: 108,376 bytes
    install ok: channel://pear.phpunit.de/PHP_CodeCoverage-0.9.0

After the installation you can find the PHP_CodeCoverage source files inside your local PEAR directory; the path is usually `/usr/lib/php/PHP/CodeCoverage`.

Using the PHP_CodeCoverage API
------------------------------

    <?php
    require_once 'PHP/CodeCoverage.php';
    require_once 'PHP/CodeCoverage/Report/Clover.php';
    require_once 'PHP/CodeCoverage/Report/HTML.php';

    $coverage = new PHP_CodeCoverage;
    $coverage->start('<name of test>');

    // ...

    $coverage->stop();

    $writer = new PHP_CodeCoverage_Report_Clover;
    $writer->process($coverage, '/tmp/clover.xml');

    $writer = new PHP_CodeCoverage_Report_HTML;
    $writer->process($coverage, '/tmp/code-coverage-report');

Using the `phpcov` tool
-----------------------

    sb@vmware examples % cat add.php
    <?php
    $a = 1;
    $b = 2;
    print $a + $b;

    sb@vmware examples % phpcov --clover clover.xml add.php
    phpcov 0.9.0 by Sebastian Bergmann.

    3

    sb@vmware examples % cat clover.xml
    <?xml version="1.0" encoding="UTF-8"?>
    <coverage generated="1270365900">
      <project timestamp="1270365900">
        <file name="/usr/local/src/bytekit-cli/examples/add.php">
          <line num="2" type="stmt" count="1"/>
          <line num="3" type="stmt" count="1"/>
          <line num="4" type="stmt" count="1"/>
          <line num="5" type="stmt" count="1"/>
          <metrics loc="4" ncloc="4" classes="0" methods="0"
                   coveredmethods="0" conditionals="0"
                   coveredconditionals="0" statements="4"
                   coveredstatements="4" elements="4"
                   coveredelements="4"/>
        </file>
        <metrics files="1" loc="4" ncloc="4" classes="0" methods="0"
                 coveredmethods="0" conditionals="0"
                 coveredconditionals="0" statements="4"
                 coveredstatements="4" elements="4"
                 coveredelements="4"/>
      </project>
    </coverage>
