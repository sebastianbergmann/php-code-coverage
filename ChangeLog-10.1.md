# ChangeLog

All notable changes are documented in this file using the [Keep a CHANGELOG](http://keepachangelog.com/) principles.

## [10.1.1] - 2023-04-17

### Fixed

* [#994](https://github.com/sebastianbergmann/php-code-coverage/issues/994): Argument `$linesToBeIgnored` of `CodeCoverage::stop()` has no effect for files that are not executed at all

## [10.1.0] - 2023-04-13

### Added

* [#982](https://github.com/sebastianbergmann/php-code-coverage/issues/982): Add option to ignore lines from code coverage

### Deprecated

* The `SebastianBergmann\CodeCoverage\Filter::includeDirectory()`, `SebastianBergmann\CodeCoverage\Filter::excludeDirectory()`, and `SebastianBergmann\CodeCoverage\Filter::excludeFile()` methods are now deprecated

[10.1.1]: https://github.com/sebastianbergmann/php-code-coverage/compare/10.1.0...10.1.1
[10.1.0]: https://github.com/sebastianbergmann/php-code-coverage/compare/10.0.2...10.1.0
