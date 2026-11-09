# ChangeLog

All notable changes are documented in this file using the [Keep a CHANGELOG](http://keepachangelog.com/) principles.

## [14.5.2] - 2026-11-09

### Fixed

* [#1379](https://github.com/sebastianbergmann/php-code-coverage/issues/1379): Opening line of a `match` nested in an arm of a `match` that is compiled to a jump table is treated as executable
* [#1380](https://github.com/sebastianbergmann/php-code-coverage/issues/1380): Opening line of a `match` is reported as not covered when merging coverage data from processes that compiled the file differently

## [14.5.1] - 2026-11-02

### Fixed

* [#1372](https://github.com/sebastianbergmann/php-code-coverage/issues/1372): Code on the opening or closing line of a multi-line `match` is not treated as executable

## [14.5.0] - 2026-10-30

### Added

* Optional `$size` parameter for `CodeCoverage::append()` that, when passed, is used instead of the size that was passed to `start()`

[14.5.2]: https://github.com/sebastianbergmann/php-code-coverage/compare/14.5.1...14.5.2
[14.5.1]: https://github.com/sebastianbergmann/php-code-coverage/compare/14.5.0...14.5.1
[14.5.0]: https://github.com/sebastianbergmann/php-code-coverage/compare/14.4.1...14.5.0
