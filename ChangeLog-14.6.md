# ChangeLog

All notable changes are documented in this file using the [Keep a CHANGELOG](http://keepachangelog.com/) principles.

## [14.6.0] - 2026-MM-DD

### Added

* On PHP 8.6 and later, the executable lines of a file can be determined from its bytecode instead of from its syntax
  * This is done when the lines that the driver reports do not depend on how PHP compiled the code, or when OPcache compiles the code without optimizations (`opcache.optimization_level=0`) and does not skip recently modified files (`opcache.file_update_protection=0`)
  * The bytecode is compiled, but not executed, by a separate PHP process that is started without a configuration file. OPcache dumps it before optimization, with line numbers (`opcache.opt_debug_level=0x40010000`). The result therefore does not depend on how the process that collects code coverage is configured, or on which classes it has already loaded
  * A line is executable when the bytecode has an opcode on that line that both Xdebug and PCOV report, other than the return statement that the compiler adds at the end of every function and file
  * All files whose analysis is not cached yet are compiled in one process. Results are cached separately from those of the syntax analysis, and separately for each PHP version
  * Files that cannot be compiled are analysed based on their syntax
* `Driver::linesDependOnCompilation()` declares whether the lines that a driver reports depend on how PHP compiled the code. It returns `true` by default, which is correct for drivers that observe the bytecode PHP executes, such as Xdebug and PCOV
* An optional `$classifier` parameter for `CacheWarmer::warmCache()`, so that the cache can also be warmed with results of the bytecode analysis

### Changed

* When Xdebug or PCOV is used on PHP 8.6 and later, and OPcache is enabled without optimizations and with `opcache.file_update_protection=0`, executable lines are determined from bytecode. Lines that have no opcodes Xdebug or PCOV report, such as the continuation lines of a multi-line function call, are no longer counted as executable
* When the code coverage data of a test is processed, the status of a line is only propagated to the other lines of its branch for the lines that share their branch with another line, instead of for every executable line of each file the test executed code in. This makes processing the code coverage data of a test faster
* `Filter::includeFile()` and `Filter::includeFiles()` no longer resolve the path of a file that is already included under the given path. This makes `CodeCoverage::merge()`, which includes the files of the filter of the object that is merged, faster when the code coverage of many processes is merged
* The HTML report lists a directory that contains nothing but another directory, or nothing but a single file, together with what it contains (`Report/Html/Renderer`, for example) instead of requiring a click for each level. The same applies to a namespace that contains nothing but another namespace

[14.6.0]: https://github.com/sebastianbergmann/php-code-coverage/compare/14.5...main
