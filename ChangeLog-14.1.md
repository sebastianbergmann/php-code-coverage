# ChangeLog

All notable changes are documented in this file using the [Keep a CHANGELOG](http://keepachangelog.com/) principles.

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

[14.1.3]: https://github.com/sebastianbergmann/php-code-coverage/compare/14.1.2...14.1.3
[14.1.2]: https://github.com/sebastianbergmann/php-code-coverage/compare/14.1.1...14.1.2
[14.1.1]: https://github.com/sebastianbergmann/php-code-coverage/compare/14.1.0...14.1.1
[14.1.0]: https://github.com/sebastianbergmann/php-code-coverage/compare/14.0.0...14.1.0
