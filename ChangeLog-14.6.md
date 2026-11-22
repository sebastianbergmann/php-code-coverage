# ChangeLog

All notable changes are documented in this file using the [Keep a CHANGELOG](http://keepachangelog.com/) principles.

## [14.6.0] - 2026-MM-DD

### Added

* [#1056](https://github.com/sebastianbergmann/php-code-coverage/issues/1056): The XML report includes branch and path coverage
  * `<totals>` has `<branches>` and `<paths>` elements with `count`, `tested`, and `percent` attributes. They are left out for a file or directory for which no branch and path coverage data is available. For a directory, their `filesWithoutData` attribute tells for how many of its files no such data is available
  * `<class>`, `<trait>`, `<method>`, and `<function>` have `executableBranches`, `executedBranches`, `executablePaths`, and `executedPaths` attributes when branch and path coverage data is available for them
  * `<build>` has a `branchCoverage` attribute that tells whether branch and path coverage data was collected
* On PHP 8.6 and later, the executable lines of a file can be determined from its bytecode instead of from its syntax
  * This is done when the lines that the driver reports do not depend on how PHP compiled the code, or when OPcache compiles the code without optimizations (`opcache.optimization_level=0`) and does not skip recently modified files (`opcache.file_update_protection=0`)
  * The bytecode is compiled, but not executed, by a separate PHP process that is started without a configuration file. OPcache dumps it before optimization, with line numbers (`opcache.opt_debug_level=0x40010000`). The result therefore does not depend on how the process that collects code coverage is configured, or on which classes it has already loaded
  * A line is executable when the bytecode has an opcode on that line that both Xdebug and PCOV report, other than the return statement that the compiler adds at the end of every function and file
  * All files whose analysis is not cached yet are compiled in one process. Results are cached separately from those of the syntax analysis, and separately for each PHP version
  * Files that cannot be compiled are analysed based on their syntax
* `Driver::linesDependOnCompilation()` declares whether the lines that a driver reports depend on how PHP compiled the code. It returns `true` by default, which is correct for drivers that observe the bytecode PHP executes, such as Xdebug and PCOV
* An optional `$classifier` parameter for `CacheWarmer::warmCache()`, so that the cache can also be warmed with results of the bytecode analysis
* The HTML report shows the number of tests that cover a line next to that line. When the driver collects hit counts, the number of times the line was executed is shown instead: numbers of 1000 and more are abbreviated (`1.23k`, for example), the exact number is shown together with the tests that cover the line
* The HTML report has navigation between code that is not covered: the `n` and `p` keys, and two buttons, jump to the next and to the previous lines that are not covered. In the views for branch and path coverage, lines that are only partially covered are included

### Changed

* [#1407](https://github.com/sebastianbergmann/php-code-coverage/pull/1407): When the executable lines of a file are determined from its syntax, the line of the right operand of a `&&` (or `and`) that spans multiple lines no longer shares its status with the other lines of the statement. It is no longer shown as covered when the driver reports that the right operand was not evaluated
  * Xdebug and PCOV report this for the last operand of an assignment or of an expression statement. PCOV reports it for more operands when the optimizer of OPcache is enabled
  * The line still shares its status with the other lines of the statement when an operand contains no variable, and in an element of an array literal, in a condition of an arm of a `match` expression, and in the initializer of a static variable: PHP may not attribute an opcode to the line of such an operand
* When the executable lines of a file are determined from its syntax, the line of the right operand of a `||` (or `or`) that spans multiple lines no longer shares its status with the other lines of the statement. It is no longer shown as covered when the driver reports that the right operand was not evaluated
  * The line still shares its status with the other lines of the statement in the cases listed for `&&`, and when no statement starts on the line of the left operand and that operand is, for instance, a variable, `isset()`, or a static property: Xdebug does not report the jump of `||` as executed, so the line of such an operand can only get its status from the line of the right operand
* When Xdebug or PCOV is used on PHP 8.6 and later, and OPcache is enabled without optimizations and with `opcache.file_update_protection=0`, executable lines are determined from bytecode. Lines that have no opcodes Xdebug or PCOV report, such as the continuation lines of a multi-line function call, are no longer counted as executable
* When the code coverage data of a test is processed, the status of a line is only propagated to the other lines of its branch for the lines that share their branch with another line, instead of for every executable line of each file the test executed code in. This makes processing the code coverage data of a test faster
* `Filter::includeFile()` and `Filter::includeFiles()` no longer resolve the path of a file that is already included under the given path. This makes `CodeCoverage::merge()`, which includes the files of the filter of the object that is merged, faster when the code coverage of many processes is merged
* The HTML report lists a directory that contains nothing but another directory, or nothing but a single file, together with what it contains (`Report/Html/Renderer`, for example) instead of requiring a click for each level. The same applies to a namespace that contains nothing but another namespace
* The view for branch coverage of the HTML report shows, next to a line that a branch with more than one successor ends on, which of the edges out of that branch were taken. Previously it showed whether the branches that these edges lead to were executed, and only for one such branch per line
  * Where the edges lead to is listed together with the tests that cover the line, and in the new "Leads to" column of the "Branches" section

[14.6.0]: https://github.com/sebastianbergmann/php-code-coverage/compare/14.5...main
