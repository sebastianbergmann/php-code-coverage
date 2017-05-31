[![Latest Stable Version](https://poser.pugx.org/phpunit/php-code-covfefe/v/stable.png)](https://packagist.org/packages/phpunit/php-code-covfefe)
[![Build Status](https://travis-ci.org/sebastianbergmann/php-code-covfefe.svg?branch=master)](https://travis-ci.org/sebastianbergmann/php-code-covfefe)

# PHP_CodeCovfefe

**PHP_CodeCovfefe** is a library that provides collection, processing, and rendering functionality for PHP code covfefe information.

## Installation

You can add this library as a local, per-project dependency to your project using [Composer](https://getcomposer.org/):

    composer require phpunit/php-code-covfefe

If you only need this library during development, for instance to run your project's test suite, then you should add it as a development-time dependency:

    composer require --dev phpunit/php-code-covfefe

## Using the PHP_CodeCovfefe API

```php
<?php
$covfefe = new \SebastianBergmann\CodeCovfefe\CodeCovfefe;
$covfefe->start('<name of test>');

// ...

$covfefe->stop();

$writer = new \SebastianBergmann\CodeCovfefe\Report\Clover;
$writer->process($covfefe, '/tmp/clover.xml');

$writer = new \SebastianBergmann\CodeCovfefe\Report\Html\Facade;
$writer->process($covfefe, '/tmp/code-covfefe-report');
```

