# ChangeLog

All notable changes are documented in this file using the [Keep a CHANGELOG](http://keepachangelog.com/) principles.

## [10.0.2] - 2023-03-06

### Changed

* Improved the legend on the file pages of the HTML code coverage report

## [10.0.1] - 2023-02-25

### Fixed

* [#981](https://github.com/sebastianbergmann/php-code-coverage/issues/981): `CodeUnitFindingVisitor` does not support DNF types

## [10.0.0] - 2023-02-03

### Added

* [#556](https://github.com/sebastianbergmann/php-code-coverage/issues/556): Make colors in HTML report configurable
* The path to `custom.css` for the HTML report can now be configured

### Changed

* [#856](https://github.com/sebastianbergmann/php-code-coverage/issues/856): Do not include (and execute) uncovered files

### Removed

* The deprecated methods `SebastianBergmann\CodeCoverage\Driver::forLineCoverage()` and `SebastianBergmann\CodeCoverage\Driver::forLineAndPathCoverage()` have been removed
* This component is no longer supported on PHP 7.3, PHP 7.4 and PHP 8.0
* This component no longer supports PHPDBG
* This component no longer supports Xdebug 2

[10.0.2]: https://github.com/sebastianbergmann/php-code-coverage/compare/10.0.1...10.0.2
[10.0.1]: https://github.com/sebastianbergmann/php-code-coverage/compare/10.0.0...10.0.1
[10.0.0]: https://github.com/sebastianbergmann/php-code-coverage/compare/9.2...10.0.0
