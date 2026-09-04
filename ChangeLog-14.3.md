# ChangeLog

All notable changes are documented in this file using the [Keep a CHANGELOG](http://keepachangelog.com/) principles.

## [14.3.2] - 2026-09-04

### Fixed

* [#1305](https://github.com/sebastianbergmann/php-code-coverage/issues/1305): Lines that the driver cannot report are marked as not covered
* No branch coverage was collected when Xdebug was used and branch coverage was requested without path coverage (`Granularity::LineAndBranch`) (Xdebug was started without `XDEBUG_CC_BRANCH_CHECK` and only line coverage was collected)

## [14.3.1] - 2026-08-16

### Changed

* The test size filter of the HTML report now disables the buttons for test sizes that have no coverage data
* The test size filter of the HTML report is no longer rendered when the coverage data has no test sizes at all

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
* The HTML report no longer depends on Bootstrap and jQuery; its markup, stylesheet, and JavaScript have been rewritten
* The headline coverage figures of a directory, file, namespace, or class are now shown above its coverage table instead of in a `Total` row
* The dashboard shows the bubble chart and the CRAP index table side by side, for classes as well as for methods
* The CSS custom properties that carry the configured colors were renamed from `--phpunit-*` to `--coverage-*`; custom CSS files that use these, or that use Bootstrap's `--bs-*` custom properties, need to be updated
* `SebastianBergmann\CodeCoverage\Report\Html\Colors::default()` now uses `#eef1f5` and `#22272e` for the breadcrumb colors instead of `var(--bs-gray-200)` and `var(--bs-gray-800)`
* The file listing of a directory no longer links directly to the branch and path coverage views of a file; these are reachable through the tabs on the file's own page
* The serialization format for `.cov` files was bumped from version 1 to version 2; files serialized with previous versions cannot be loaded or merged any more

### Fixed

* [#1258](https://github.com/sebastianbergmann/php-code-coverage/issues/1258): Coverage of less than 100% can be displayed as 100.00% due to rounding

[14.3.2]: https://github.com/sebastianbergmann/php-code-coverage/compare/14.3.1...14.3.2
[14.3.1]: https://github.com/sebastianbergmann/php-code-coverage/compare/14.3.0...14.3.1
[14.3.0]: https://github.com/sebastianbergmann/php-code-coverage/compare/14.2.4...14.3.0
