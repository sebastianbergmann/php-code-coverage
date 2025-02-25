# ChangeLog

All notable changes are documented in this file using the [Keep a CHANGELOG](http://keepachangelog.com/) principles.

## [12.0.4] - 2025-02-25

### Fixed

* [#1063](https://github.com/sebastianbergmann/php-code-coverage/issues/1063): HTML report highlights argument named `fn` differently than other named arguments

## [12.0.3] - 2025-02-18

### Fixed

* `#CoversClass` does not target code in parent class(es)

## [12.0.2] - 2025-02-08

### Changed

* Changed version identifier for static analysis cache from "MD5 over source code" to `Version::id()`

## [12.0.1] - 2025-02-07

### Fixed

* [#1061](https://github.com/sebastianbergmann/php-code-coverage/issues/1061): Enumerations cannot be targeted for code coverage

## [12.0.0] - 2025-02-07

### Changed

* `CodeCoverage::stop()` and `CodeCoverage::append()` now expect arguments of type `TargetCollection` instead of `array` to configure code coverage targets

### Removed

* Method `CodeCoverage::detectsDeadCode()`
* Optional argument `$linesToBeUsed` of `CodeCoverage::stop()` and `CodeCoverage::append()` methods
* This component is no longer supported on PHP 8.2
* This component no longer supports Xdebug versions before Xdebug 3.1

[12.0.4]: https://github.com/sebastianbergmann/php-code-coverage/compare/12.0.3...12.0.4
[12.0.3]: https://github.com/sebastianbergmann/php-code-coverage/compare/12.0.2...12.0.3
[12.0.2]: https://github.com/sebastianbergmann/php-code-coverage/compare/12.0.1...12.0.2
[12.0.1]: https://github.com/sebastianbergmann/php-code-coverage/compare/12.0.0...12.0.1
[12.0.0]: https://github.com/sebastianbergmann/php-code-coverage/compare/11.0...12.0.0
