# ChangeLog

All notable changes are documented in this file using the [Keep a CHANGELOG](http://keepachangelog.com/) principles.

## [12.5.2] - 2025-12-24

### Fixed

* [#1131](https://github.com/sebastianbergmann/php-code-coverage/issues/1131): Invalid XML generated when both PCOV and Xdebug are loaded

## [12.5.1] - 2025-12-08

### Changed

* [#1125](https://github.com/sebastianbergmann/php-code-coverage/pull/1125): Improve performance of XML report by using XMLWriter instead of DOM

## [12.5.0] - 2025-11-29

### Added

* Option to not generate the `<source>` element for the XML report

### Changed

* [#1102](https://github.com/sebastianbergmann/php-code-coverage/pull/1102), [#1111](https://github.com/sebastianbergmann/php-code-coverage/pull/1111), [#1112](https://github.com/sebastianbergmann/php-code-coverage/pull/1112), [#1113](https://github.com/sebastianbergmann/php-code-coverage/pull/1113), [#1114](https://github.com/sebastianbergmann/php-code-coverage/pull/1114), [#1115](https://github.com/sebastianbergmann/php-code-coverage/pull/1115), [#1116](https://github.com/sebastianbergmann/php-code-coverage/pull/1116), [#1117](https://github.com/sebastianbergmann/php-code-coverage/pull/1117), [#1118](https://github.com/sebastianbergmann/php-code-coverage/pull/1118), [#1119](https://github.com/sebastianbergmann/php-code-coverage/pull/1119), [#1120](https://github.com/sebastianbergmann/php-code-coverage/pull/1120), [#1121](https://github.com/sebastianbergmann/php-code-coverage/pull/1121), [#1122](https://github.com/sebastianbergmann/php-code-coverage/pull/1122), [#1123](https://github.com/sebastianbergmann/php-code-coverage/pull/1123), [#1124](https://github.com/sebastianbergmann/php-code-coverage/pull/1124): Improve performance of XML report
* [#1107](https://github.com/sebastianbergmann/php-code-coverage/pull/1107): Do not sort code coverage data over and over
* [#1108](https://github.com/sebastianbergmann/php-code-coverage/pull/1108): Do not sort covered files data over and over
* [#1109](https://github.com/sebastianbergmann/php-code-coverage/pull/1109): Represent line coverage data using objects
* [#1126](https://github.com/sebastianbergmann/php-code-coverage/issues/1126): Add test execution time to `<test>` elements under `projects/tests` in the XML reports index file
* [#1127](https://github.com/sebastianbergmann/php-code-coverage/issues/1127): Add SHA-1 hash of content of SUT source file to XML report

[12.5.2]: https://github.com/sebastianbergmann/php-code-coverage/compare/12.5.1...12.5.2
[12.5.1]: https://github.com/sebastianbergmann/php-code-coverage/compare/12.5.0...12.5.1
[12.5.0]: https://github.com/sebastianbergmann/php-code-coverage/compare/12.4.0...12.5.0
