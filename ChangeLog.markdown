PHP_CodeCoverage 1.1
====================

This is the list of changes for the PHP_CodeCoverage 1.1 release series.

PHP_CodeCoverage 1.1.3
----------------------

* Fixed #94: Functions created using the runkit extension caused warnings.

PHP_CodeCoverage 1.1.2
----------------------

* Fixed #80: Whitelisted files that do not exist lead to errors.
* Fixed #91: Traits are not handled properly.
* Fixed notice in `PHP_CodeCoverage_Util::resolveCoversToReflectionObjects()`.
* The `callbable`, `implements`, and `insteadof` keywords are now properly highlighted in the HTML report.

PHP_CodeCoverage 1.1.1
----------------------

* Fixed #74: Removed extraneous spaces in displayed paths in the HTML code coverage report.
* Fixed #75: CRAP index missing from HTML code coverage report.

PHP_CodeCoverage 1.1.0
----------------------

* Added support for traits.
* Added an option to disable the caching of `PHP_Token_Stream` objects to reduce the memory usage.
* Refactored the collection and processing of code coverage data improving code readability and performance.
* Refactored the generation of Clover XML and HTML reports improving code readability and testability.
* Removed the support for multiple blacklist groups.
* Removed the `PHP_CodeCoverage::getInstance()` and `PHP_CodeCoverage_Filter::getInstance()` methods.
* Replaced [RGraph](http://www.rgraph.net/) with [Highcharts JS](http://www.highcharts.com/) as the JavaScript chart library used for the HTML report's code coverage dashboard.
