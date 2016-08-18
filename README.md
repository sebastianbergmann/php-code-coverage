[![Latest Stable Version](https://poser.pugx.org/phpunit/php-code-coverage/v/stable.png)](https://packagist.org/packages/phpunit/php-code-coverage)
[![Build Status](https://travis-ci.org/sebastianbergmann/php-code-coverage.svg?branch=master)](https://travis-ci.org/sebastianbergmann/php-code-coverage)

# PHP_CodeCoverage

**PHP_CodeCoverage** is a library that provides collection, processing, and rendering functionality for PHP code coverage information.

## Requirements

PHP 5.6 is required but using the latest version of PHP is highly recommended.

### PHP 5

[Xdebug](http://xdebug.org/) is the only source of raw code coverage data supported for PHP 5. Version 2.2.1 of Xdebug is required but using the latest version is highly recommended.

### PHP 7

Version 2.4.0 (or later) of [Xdebug](http://xdebug.org/) as well as [phpdbg](http://phpdbg.com/docs) are supported sources of raw code coverage data for PHP 7.

### HHVM

A version of HHVM that implements the Xdebug API for code coverage (`xdebug_*_code_coverage()`) is required.

## Installation

To add PHP_CodeCoverage as a local, per-project dependency to your project, simply add a dependency on `phpunit/php-code-coverage` to your project's `composer.json` file. Here is a minimal example of a `composer.json` file that just defines a dependency on PHP_CodeCoverage 3.0:

    {
        "require": {
            "phpunit/php-code-coverage": "^4"
        }
    }

## Using the PHP_CodeCoverage API

```php
<?php
$coverage = new \SebastianBergmann\CodeCoverage\CodeCoverage;
$coverage->start('<name of test>');

// ...

$coverage->stop();

$writer = new \SebastianBergmann\CodeCoverage\Report\Clover;
$writer->process($coverage, '/tmp/clover.xml');

$writer = new \SebastianBergmann\CodeCoverage\Report\Html\Facade;
$writer->process($coverage, '/tmp/code-coverage-report');
```
