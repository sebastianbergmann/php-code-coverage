PHP_CodeCoverage 1.0
====================

This is the list of changes for the PHP_CodeCoverage 1.0 release series.

PHP_CodeCoverage 1.0.5
----------------------

* Fixed sorting of top project risks.

PHP_CodeCoverage 1.0.4
----------------------

* Fixed an issue where `mkdir()` was called with a wrong argument.
* Updated list of dependencies in `package.xml`.

PHP_CodeCoverage 1.0.3
----------------------

* Fixed GH-32: `//@codeCoverageIgnore*` (no leading space) no longer works.
* Fixed a bug in `PHP_CodeCoverage_Report_HTML_Node_File::getNumClasses()`.
* Abstract methods are now excluded from code coverage statistics.
* When the directory to which the Clover XML logfile is to be written does not exist it is created.
* Updated bundled RGraph library to version 2010-12-24.
* Updated bundled YUI library to version 2.8.2r1.

PHP_CodeCoverage 1.0.2
----------------------

* Fixed the `version_compare()` check for Xdebug 2.2.

PHP_CodeCoverage 1.0.1
----------------------

* Covered lines of uncovered files are now correctly marked as uncovered.
* Fixed the detection of uncovered files.
* A warning is now printed when Xdebug 2.2 (or later) is used and `xdebug.coverage_enable=0` is set.
* Various minor performance optimizations.

PHP_CodeCoverage 1.0.0
----------------------

* Initial release.
