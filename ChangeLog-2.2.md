# Changes in PHP_CodeCoverage 2.2

All notable changes of the PHP_CodeCoverage 2.2 release series are documented in this file using the [Keep a CHANGELOG](http://keepachangelog.com/) principles.

## [2.2.0] - 2015-08-02

### Changed

* Bumped required version of `sebastian/environment` to 1.3.1 for [#365](https://github.com/sebastianbergmann/php-code-coverage/issues/365)

## [2.2.0] - 2015-08-01

### Added

* Added a driver for PHPDBG (requires PHP 7)
* Added `PHP_CodeCoverage::setDisableIgnoredLines()` to disable the ignoring of lines using annotations such as `@codeCoverageIgnore`

### Changed

* Annotating a method with `@deprecated` now has the same effect as annotating it with `@codeCoverageIgnore`

### Removed

* The dedicated driver for HHVM, `PHP_CodeCoverage_Driver_HHVM` has been removed

[2.2.0]: https://github.com/sebastianbergmann/php-code-coverage/compare/2.1...2.2.0

