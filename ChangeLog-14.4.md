# ChangeLog

All notable changes are documented in this file using the [Keep a CHANGELOG](http://keepachangelog.com/) principles.

## [14.4.0] - 2026-10-02

### Added

* `SebastianBergmann\CodeCoverage\Report\Facade::renderJsonl()` writes `meta.json`, `coverage.jsonl`, and `tests.jsonl` to a directory
  * `meta.json` reports the schema version, the generator, the time of generation, the source root, whether branch coverage was collected, and the number of files, executable lines, and executed lines
  * `coverage.jsonl` reports one JSON object per line, one for each source file that has executable code: its path relative to the source root, its number of executable and executed lines, its executable
    lines that were not executed, and one entry per method and function
    * The entry for a method or function reports its first and last line, whether it is `covered`, `partial`, or `uncovered`, and, when it is `partial`, its executable lines that were not executed
    * When branch coverage was collected, the entry for a method or function also reports how many of its branches were executed and how many it has, as well as the lines at which a branch was not taken; a
      method or function whose lines were all executed but whose branches were not is reported as `partial`
  * `tests.jsonl` reports one JSON object per line, one for each test that executed at least one line: the identifier of the test, and per source file the lines that the test executed
  * Consecutive line numbers are reported as `"45-52"`, single line numbers as `"77"`
  * Source files without executable code are not reported; records are sorted by path and by test identifier, and paths use `/` on all platforms, so that the same coverage data yields byte-identical output
* `enableCollectionOfDataNotFilteredUsingTargets()` and `dataNotFilteredUsingTargets()` to allow collection of code coverage data that is not filtered using code coverage targets

[14.4.0]: https://github.com/sebastianbergmann/php-code-coverage/compare/14.3...main
