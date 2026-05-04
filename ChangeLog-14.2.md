# ChangeLog

All notable changes are documented in this file using the [Keep a CHANGELOG](http://keepachangelog.com/) principles.

## [14.2.0] - 2026-MM-DD

### Added

* `SebastianBergmann\CodeCoverage\Driver\Granularity` enumeration with `Line`, `LineAndBranch`, and `LineBranchAndPath` cases
* `SebastianBergmann\CodeCoverage\Driver\Selector::driver(Filter, Granularity)` for selecting a driver by required granularity
* `SebastianBergmann\CodeCoverage\NoSupportedDriverAvailableException` thrown when no driver supports the requested granularity

### Changed

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

* [#1154](https://github.com/sebastianbergmann/php-code-coverage/issues/1154): Opening and closing lines of `match (true)` expressions are reported as not executed
* [#1156](https://github.com/sebastianbergmann/php-code-coverage/issues/1156): Scalar literals produce incorrect code coverage information

[14.2.0]: https://github.com/sebastianbergmann/php-code-coverage/compare/14.1.6...main
