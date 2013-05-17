# PHP_CodeCoverage

**PHP_CodeCoverage** is a library that provides collection, processing, and rendering functionality for PHP code coverage information.

## Requirements

* PHP_CodeCoverage 1.2 requires PHP 5.3.3 (or later) but PHP 5.4.7 (or later) is highly recommended.
* [Xdebug](http://xdebug.org/) 2.0.5 (or later) is required but Xdebug 2.2.1 (or later) is highly recommended.

## Installation

You can use the [PEAR Installer](http://pear.php.net/manual/en/guide.users.commandline.cli.php) or [Composer](http://getcomposer.org/) to download and install PHP_CodeCoverage as well as its dependencies

### PEAR Installer

Depending on your OS distribution and/or your PHP environment, you may need to install PEAR or update your existing PEAR installation before you can proceed with the following instructions. `sudo pear upgrade PEAR` usually suffices to upgrade an existing PEAR installation. The [PEAR Manual ](http://pear.php.net/manual/en/installation.getting.php) explains how to perform a fresh installation of PEAR.

The following two commands (which you may have to run as `root`) are all that is required to install PHP_CodeCoverage using the PEAR Installer:

    pear config-set auto_discover 1
    pear install pear.phpunit.de/PHP_CodeCoverage

After the installation you can find the PHP_CodeCoverage source files inside your local PEAR directory; the path is usually `/usr/lib/php/PHP/CodeCoverage`.

### Composer

To add PHP_CodeCoverage as a local, per-project dependency to your project, simply add a dependency on `phpunit/php-code-coverage` to your project's `composer.json` file. Here is a minimal example of a `composer.json` file that just defines a dependency on PHP_CodeCoverage 1.2:

    {
        "require": {
            "phpunit/php-code-coverage": ">=1.2.10,<1.3.0"
        }
    }

## Using the PHP_CodeCoverage API

```php
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
```