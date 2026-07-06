# ChangeLog

All notable changes are documented in this file using the [Keep a CHANGELOG](http://keepachangelog.com/) principles.

## [14.2.3] - 2026-07-06

### Fixed

* [#1240](https://github.com/sebastianbergmann/php-code-coverage/issues/1240): Dark-mode HTML report: Warning and Danger backgrounds are indistinguishable for colorblind users
* [#1244](https://github.com/sebastianbergmann/php-code-coverage/pull/1244): Do not build code unit map when test has no coverage targets

## [14.2.2] - 2026-06-08

### Fixed

* [#1230](https://github.com/sebastianbergmann/php-code-coverage/issues/1230): Short-form property hook bodies wrongly classified as executable

## [14.2.1] - 2026-06-07

### Added

* `RawCodeCoverageData::fromLineAndBranchCoverage()`

## [14.2.0] - 2026-06-05

### Added

* `SebastianBergmann\CodeCoverage\Driver\Granularity` enumeration with `Line`, `LineAndBranch`, and `LineBranchAndPath` cases
* `SebastianBergmann\CodeCoverage\Driver\Selector::driver(Filter, Granularity)` for selecting a driver by required granularity
* `SebastianBergmann\CodeCoverage\NoSupportedDriverAvailableException` thrown when no driver supports the requested granularity

### Changed

* [#1186](https://github.com/sebastianbergmann/php-code-coverage/pull/1186): `Merger::merge()` now accepts any kind of iterable
* Reduced overhead in static analysis
* The HTML report now distinguishes three rendering modes (line, line + branch, line + branch + path) so that a driver providing branch coverage without path coverage no longer forces path-coverage UI to appear with empty data
* The Text report now gates the `Branches:` and `Paths:` lines on the presence of the corresponding data independently
* Various internal refactorings to improve code readability

### Deprecated

* `SebastianBergmann\CodeCoverage\Driver\Selector::forLineCoverage()`, use `Selector::select()` with `Granularity::Line` instead
* `SebastianBergmann\CodeCoverage\Driver\Selector::forLineAndPathCoverage()`, use `Selector::select()` with `Granularity::LineBranchAndPath` instead
* `SebastianBergmann\CodeCoverage\CodeCoverage::enableBranchAndPathCoverage()`, use `Selector::select()` with `Granularity::LineBranchAndPath` instead
* `SebastianBergmann\CodeCoverage\CodeCoverage::disableBranchAndPathCoverage()`, use `Selector::select()` with `Granularity::Line` instead
* `SebastianBergmann\CodeCoverage\CodeCoverage::collectsBranchAndPathCoverage()`
* `SebastianBergmann\CodeCoverage\BranchAndPathCoverageNotSupportedException`, replaced by `BranchCoverageNotSupportedException` and `PathCoverageNotSupportedException`

### Fixed

* [#1159](https://github.com/sebastianbergmann/php-code-coverage/issues/1159): Statements inside a closure passed as a call argument are incorrectly reported as not covered

[14.2.3]: https://github.com/sebastianbergmann/php-code-coverage/compare/14.2.2...14.2.3
[14.2.2]: https://github.com/sebastianbergmann/php-code-coverage/compare/14.2.1...14.2.2
[14.2.1]: https://github.com/sebastianbergmann/php-code-coverage/compare/14.2.0...14.2.1
[14.2.0]: https://github.com/sebastianbergmann/php-code-coverage/compare/14.1.10...14.2.0
