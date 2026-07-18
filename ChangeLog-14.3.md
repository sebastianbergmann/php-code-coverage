# ChangeLog

All notable changes are documented in this file using the [Keep a CHANGELOG](http://keepachangelog.com/) principles.

## [14.3.0] - 2026-08-07

### Added

* [#1140](https://github.com/sebastianbergmann/php-code-coverage/pull/1140): Class-oriented HTML report
* [#1141](https://github.com/sebastianbergmann/php-code-coverage/pull/1141): Improve visualization of branch coverage and path coverage in the HTML report
* [#1153](https://github.com/sebastianbergmann/php-code-coverage/pull/1153): Filter HTML code coverage report by test size
* [#1210](https://github.com/sebastianbergmann/php-code-coverage/pull/1210): Filesystem-based targeting
* [#1231](https://github.com/sebastianbergmann/php-code-coverage/pull/1231): Identify dead code using static analysis
* Record how often a test executed a line or traversed a branch or path (the `<covered>` elements of the XML report now have a `count` attribute; drivers that do not collect hit counts report `1`)

### Changed

* [#1259](https://github.com/sebastianbergmann/php-code-coverage/issues/1259): Degrade gracefully when a source file cannot be parsed
* The serialization format for `.cov` files was bumped from version 1 to version 2; files serialized with previous versions cannot be loaded or merged any more

### Fixed

* [#1258](https://github.com/sebastianbergmann/php-code-coverage/issues/1258): Coverage of less than 100% can be displayed as 100.00% due to rounding

[14.3.0]: https://github.com/sebastianbergmann/php-code-coverage/compare/14.2.3...main
