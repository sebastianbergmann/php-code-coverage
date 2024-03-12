# ChangeLog

All notable changes are documented in this file using the [Keep a CHANGELOG](http://keepachangelog.com/) principles.

## [11.0.3] - 2024-03-12

### Fixed

* [#1033](https://github.com/sebastianbergmann/php-code-coverage/issues/1033): `@codeCoverageIgnore` annotation does not work on `enum`

## [11.0.2] - 2024-03-09

### Changed

* [#1032](https://github.com/sebastianbergmann/php-code-coverage/pull/1032): Pad lines in code coverage report only when colors are shown

## [11.0.1] - 2024-03-02

### Changed

* Do not use implicitly nullable parameters

## [11.0.0] - 2024-02-02

### Removed

* The `SebastianBergmann\CodeCoverage\Filter::includeDirectory()`, `SebastianBergmann\CodeCoverage\Filter::excludeDirectory()`, and `SebastianBergmann\CodeCoverage\Filter::excludeFile()` methods have been removed
* This component now requires PHP-Parser 5
* This component is no longer supported on PHP 8.1

[11.0.3]: https://github.com/sebastianbergmann/php-code-coverage/compare/11.0.2...11.0.3
[11.0.2]: https://github.com/sebastianbergmann/php-code-coverage/compare/11.0.1...11.0.2
[11.0.1]: https://github.com/sebastianbergmann/php-code-coverage/compare/11.0.0...11.0.1
[11.0.0]: https://github.com/sebastianbergmann/php-code-coverage/compare/10.1...11.0.0
