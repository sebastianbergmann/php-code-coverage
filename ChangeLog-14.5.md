# ChangeLog

All notable changes are documented in this file using the [Keep a CHANGELOG](http://keepachangelog.com/) principles.

## [14.5.3] - 2026-MM-DD

### Fixed

* Which edges out of a branch were taken is only recorded for the first test that executes a function. Edges that are only taken by other tests, or only in code coverage data that is merged, are shown as not covered in the control flow graphs of the HTML report and are reported as not covered by the JSONL report
  * The version of the format in which code coverage data is serialized is now 5: which edges were taken has a different meaning in files written by earlier versions, these files can no longer be read
* The control flow graphs of the HTML report show the edge that leads to the exit of a function as not covered, even when it is the only edge out of a branch that was executed
* The JSONL report reports the line that a branch ends on as not covered when one of the edges out of that branch leads to the exit of the function

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

[14.5.3]: https://github.com/sebastianbergmann/php-code-coverage/compare/14.5.2...14.5
[14.5.2]: https://github.com/sebastianbergmann/php-code-coverage/compare/14.5.1...14.5.2
[14.5.1]: https://github.com/sebastianbergmann/php-code-coverage/compare/14.5.0...14.5.1
[14.5.0]: https://github.com/sebastianbergmann/php-code-coverage/compare/14.4.1...14.5.0
