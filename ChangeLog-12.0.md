# ChangeLog

All notable changes are documented in this file using the [Keep a CHANGELOG](http://keepachangelog.com/) principles.

## [12.0.0] - 2025-02-07

### Changed

* `CodeCoverage::stop()` and `CodeCoverage::append()` now expect arguments of type `TargetCollection` instead of `array` to configure code coverage targets

### Removed

* Methods `CodeCoverage::includeUncoveredFiles()` and `CodeCoverage::excludeUncoveredFiles()`
* Optional argument `$linesToBeUsed` of `CodeCoverage::stop()` and `CodeCoverage::append()` methods
* This component is no longer supported on PHP 8.2
* This component no longer supports Xdebug versions before Xdebug 3.1

[12.0.0]: https://github.com/sebastianbergmann/php-code-coverage/compare/11.0...main
