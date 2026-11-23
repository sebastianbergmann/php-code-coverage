# ChangeLog

All notable changes are documented in this file using the [Keep a CHANGELOG](http://keepachangelog.com/) principles.

## [14.6.0] - 2026-MM-DD

### Added

#### Data Collection and Data Processing

* On PHP 8.6 and later, the executable lines of a file can be determined from its bytecode instead of from its syntax. The syntax only allows for an approximation of the lines that PHP attributes code to, the bytecode states them
  * With Xdebug and PCOV, this is done when OPcache is enabled, compiles the code without optimizations (`opcache.optimization_level=0`), and does not skip recently modified files (`opcache.file_update_protection=0`): only then is the code that is executed compiled the same way as the code that is analysed. Otherwise, the executable lines are determined from the syntax, as before
  * A line is executable when the bytecode has an opcode on that line that both Xdebug and PCOV report, other than the return statement that the compiler adds at the end of every function and file
  * The bytecode is compiled, but not executed, by a separate PHP process that is started without a configuration file. OPcache dumps it before optimization, with line numbers (`opcache.opt_debug_level=0x40010000`). The result therefore does not depend on how the process that collects code coverage is configured, or on which classes it has already loaded
  * All files whose analysis is not cached yet are compiled in one process. The results are cached separately from those of the syntax analysis, and separately for each PHP version
  * A file that cannot be compiled is analysed based on its syntax
* `Driver::linesDependOnCompilation()` declares whether the lines that a driver reports depend on how PHP compiled the code. It returns `true` by default, which is correct for drivers that observe the bytecode PHP executes, such as Xdebug and PCOV. For a driver that returns `false`, the executable lines are determined from the bytecode no matter how OPcache is configured in the process that collects code coverage
* `CacheWarmer::warmCache()` has an optional `$classifier` parameter, so that the cache can also be warmed with the results of the bytecode analysis

#### Report Generation

* [#1056](https://github.com/sebastianbergmann/php-code-coverage/issues/1056): The XML report includes branch and path coverage
  * `<build>` has a `branchCoverage` attribute that tells whether branch and path coverage data was collected
  * `<totals>` has `<branches>` and `<paths>` elements with `count`, `tested`, and `percent` attributes. They are left out for a file or directory for which no branch and path coverage data is available. For a directory, their `filesWithoutData` attribute tells for how many of its files no such data is available
  * `<class>`, `<trait>`, `<method>`, and `<function>` have `executableBranches`, `executedBranches`, `executablePaths`, and `executedPaths` attributes when branch and path coverage data is available for them
* `Report\Facade::filesThatWereNotFound()` returns the files for which there is code coverage data, but which are not part of the report because they could not be found. This is the case, for instance, when a report is generated from serialized code coverage data in a location other than the one the data was collected in
* The HTML report shows, next to a line that is covered, how many tests cover it. When the driver collects hit counts, it shows how often the line was executed instead. Numbers of 1000 and more are abbreviated (`1.23k`, for example), the exact number is shown in the popover that lists the tests that cover the line
* The HTML report has navigation between the pieces of code that are not covered: the `n` and `p` keys, and two buttons, jump to the next and to the previous one. In the views for branch coverage and path coverage, lines that are only partially covered are included

### Changed

#### Data Collection and Data Processing

* When the executable lines of a file are determined from its syntax, the lines of a statement that spans multiple lines share their status: all of them are shown as covered when one of them was executed. The following lines no longer share their status with the other lines of their statement, as PHP does not always execute the code on them. They are no longer shown as covered when the driver reports that they were not executed
  * [#1407](https://github.com/sebastianbergmann/php-code-coverage/pull/1407): The line of the right operand of a `&&` (or `and`) that spans multiple lines. This operand is not evaluated when the left operand is false. Xdebug and PCOV only report its line as not executed for the last operand of an assignment or of an expression statement, PCOV reports it for more operands when the optimizer of OPcache is enabled
  * The line of the right operand of a `||` (or `or`) that spans multiple lines. This operand is not evaluated when the left operand is true
  * The lines of a method call or property fetch after `?->` that starts on a line of its own. The method is not called, and the property is not fetched, when the object is `null`
* The lines listed above still share their status with the other lines of their statement in the following cases
  * `&&`, `||`, and `?->` in an element of an array literal, in a condition of an arm of a `match` expression, and in the initializer of a static variable
  * `&&` and `||` when an operand contains no variable. As in the cases above, PHP may not attribute an opcode to the line of such an operand
  * `||` when no statement starts on the line of the left operand and that operand is, for instance, a variable, `isset()`, or a static property. Xdebug does not report the jump of `||` as executed, so the line of such an operand can only get its status from the line of the right operand
  * `?->` when no statement starts on the line of the object and the object is, for instance, a variable. Xdebug does not report the jump of the nullsafe operator as executed, so the line of such an object can only get its status from the line of the method call or property fetch
  * `?->` in the operand of `??`, `isset()`, and `empty()`
* When the executable lines of a file are determined from its bytecode, lines that have no opcode that both Xdebug and PCOV report, such as the continuation lines of a multi-line function call, are no longer counted as executable. This affects code coverage that is collected with Xdebug or PCOV on PHP 8.6 and later when OPcache is enabled, without optimizations (`opcache.optimization_level=0`), and with `opcache.file_update_protection=0`
* Processing the code coverage data of a test is faster. The status of a line is only propagated to the other lines of its branch for the lines that share their branch with another line, instead of for every executable line of each file the test executed code in
* `CodeCoverage::merge()` is faster when the code coverage of many processes is merged. `Filter::includeFile()` and `Filter::includeFiles()`, which are used to include the files of the filter of the object that is merged, no longer resolve the path of a file that is already included under the given path

#### Report Generation

* The HTML report lists a directory that contains nothing but another directory, or nothing but a single file, together with what it contains (`Report/Html/Renderer`, for example) instead of requiring a click for each level. The same applies to a namespace that contains nothing but another namespace
* The view for branch coverage of the HTML report shows, next to a line that a branch with more than one successor ends on, which of the edges out of that branch were taken. Previously, it showed whether the branches that these edges lead to were executed, and only for one such branch per line
  * Where the edges lead to is listed in the popover of the line, together with the tests that cover the line, and in the new "Leads to" column of the "Branches" section

### Fixed

#### Data Collection and Data Processing

* [#1408](https://github.com/sebastianbergmann/php-code-coverage/issues/1408): Merging `.cov` files silently loses data when they cover different sets of files
* Which edges out of a branch were taken is only recorded for the first test that executes a function. Edges that are only taken by other tests, or only in code coverage data that is merged, are shown as not covered in the control flow graphs of the HTML report and are reported as not covered by the JSONL report

#### Serialization and Merging

* [#1408](https://github.com/sebastianbergmann/php-code-coverage/issues/1408): Merging `.cov` files silently loses data when they cover different sets of files

#### Report Generation

* Reports are empty when the root directory is all that the paths of the source files have in common, unless the current working directory is the root directory
* The control flow graphs of the HTML report show the edge that leads to the exit of a function as not covered, even when it is the only edge out of a branch that was executed
* The JSONL report reports the line that a branch ends on as not covered when one of the edges out of that branch leads to the exit of the function

[14.6.0]: https://github.com/sebastianbergmann/php-code-coverage/compare/14.5...main
