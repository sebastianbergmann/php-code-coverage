# PHP_CodeCoverage

**PHP_CodeCoverage** is a library that provides collection, processing, and rendering functionality for PHP code coverage information.

## Requirements

* PHP_CodeCoverage 1.3 requires PHP 5.4.7 (or later).
* [Xdebug](http://xdebug.org/) 2.2.1 (or later) is required.

## Installation

Simply add a dependency on `phpunit/php-code-coverage` to your project's `composer.json` file if you use [Composer](http://getcomposer.org/) to manage the dependencies of your project.

Here is a minimal example of a `composer.json` file that just defines a dependency on PHP_CodeCoverage:

    {
        "require": {
            "phpunit/php-code-coverage": "*"
        }
    }

## Using the PHP_CodeCoverage API

```php
<?php
$coverage = new PHP_CodeCoverage;
$coverage->start('<name of test>');

// ...

$coverage->stop();

$writer = new PHP_CodeCoverage_Report_Clover;
$writer->process($coverage, '/tmp/clover.xml');

$writer = new PHP_CodeCoverage_Report_HTML;
$writer->process($coverage, '/tmp/code-coverage-report');
```

