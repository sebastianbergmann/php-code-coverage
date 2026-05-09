# ChangeLog

All notable changes are documented in this file using the [Keep a CHANGELOG](http://keepachangelog.com/) principles.

## [14.1.8] - 2026-05-09

### Fixed

* [#1159](https://github.com/sebastianbergmann/php-code-coverage/issues/1159): Statements inside a closure passed as a call argument are incorrectly reported as not covered
* `case` statements are treated as branch operators

## [14.1.7] - 2026-05-04

### Fixed

* [#1154](https://github.com/sebastianbergmann/php-code-coverage/issues/1154): Opening and closing lines of `match (true)` expressions are reported as not executed
* [#1156](https://github.com/sebastianbergmann/php-code-coverage/issues/1156): Scalar literals produce incorrect code coverage information

## [14.1.6] - 2026-04-24

### Fixed

* [#1077](https://github.com/sebastianbergmann/php-code-coverage/issues/1077): `UnintentionallyCoveredCodeException` should report `ClassName::methodName` when methods are targeted

## [14.1.5] - 2026-04-24

### Changed

* [#941](https://github.com/sebastianbergmann/php-code-coverage/issues/941): Sort directories and files in strict alphabetical order

### Fixed

* [#491](https://github.com/sebastianbergmann/php-code-coverage/issues/491): Ensure strings are valid UTF-8 before passing them to XML APIs
* [#919](https://github.com/sebastianbergmann/php-code-coverage/issues/919): Not all lines of an interface are ignored
* [#1007](https://github.com/sebastianbergmann/php-code-coverage/issues/1007): Incorrect branch/path coverage totals for uncovered files
* [#1029](https://github.com/sebastianbergmann/php-code-coverage/issues/1029): Lines of multiline ternary expressions inside array literals are not shown in coverage reports
* [#1030](https://github.com/sebastianbergmann/php-code-coverage/issues/1030): Start line of code unit includes attributes

## [14.1.4] - 2026-04-23

### Fixed

* Added tokens for asymmetric visibility to the syntax highlighter used for the HTML report
* Fixed whitespace issue in the HTML report for files with long lines

## [14.1.3] - 2026-04-18

### Fixed

* [#1151](https://github.com/sebastianbergmann/php-code-coverage/issues/1151): Version check in `Unserializer::unserialize()` is too restrictive

## [14.1.2] - 2026-04-15

### Fixed

* [#1150](https://github.com/sebastianbergmann/php-code-coverage/issues/1150): Abstract method declarations are incorrectly counted as executable lines

## [14.1.1] - 2026-04-13

### Fixed

* [#1149](https://github.com/sebastianbergmann/php-code-coverage/pull/1149): Lines spanned by attributes are treated as executable

## [14.1.0] - 2026-04-12

### Added

* `SebastianBergmann\CodeCoverage\Report\Facade::summary()` method that returns a value object that provides the number of executable lines, the number of executed lines, and line coverage in percent (as well as the respective numbers for branches and paths when available)

### Changed

* The XML document of the code coverage report in Cobertura XML format no longer has the `<!DOCTYPE coverage SYSTEM "http://cobertura.sourceforge.net/xml/coverage-04.dtd">` line at the beginning. No document exists at this URL any more, referencing remote DTD URLs is problematic, and no common consumer of Cobertura XML relies on this line.

### Fixed

* [#1147](https://github.com/sebastianbergmann/php-code-coverage/pull/1147): `CoversClass` does not transitively target traits used by enumerations

[14.1.8]: https://github.com/sebastianbergmann/php-code-coverage/compare/14.1.7...14.1.8
[14.1.7]: https://github.com/sebastianbergmann/php-code-coverage/compare/14.1.6...14.1.7
[14.1.6]: https://github.com/sebastianbergmann/php-code-coverage/compare/14.1.5...14.1.6
[14.1.5]: https://github.com/sebastianbergmann/php-code-coverage/compare/14.1.4...14.1.5
[14.1.4]: https://github.com/sebastianbergmann/php-code-coverage/compare/14.1.3...14.1.4
[14.1.3]: https://github.com/sebastianbergmann/php-code-coverage/compare/14.1.2...14.1.3
[14.1.2]: https://github.com/sebastianbergmann/php-code-coverage/compare/14.1.1...14.1.2
[14.1.1]: https://github.com/sebastianbergmann/php-code-coverage/compare/14.1.0...14.1.1
[14.1.0]: https://github.com/sebastianbergmann/php-code-coverage/compare/14.0.0...14.1.0
