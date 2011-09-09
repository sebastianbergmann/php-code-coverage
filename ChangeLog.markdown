PHP_CodeCoverage 1.1
====================

This is the list of changes for the PHP_CodeCoverage 1.1 release series.

PHP_CodeCoverage 1.1.0
----------------------

* Added support for merging serialized-to-disk `PHP_CodeCoverage` objects using `phpcov --merge`.
* Added support for traits.
* Added an option to disable the caching of `PHP_Token_Stream` objects to reduce the memory usage.
* Refactored the collection and processing of code coverage data improving code readability and performance.
* Refactored the generation of Clover XML and HTML reports improving code readability and testability.
* Removed the support for multiple blacklist groups.
* Removed the `PHP_CodeCoverage::getInstance()` and `PHP_CodeCoverage_Filter::getInstance()` methods.
* Replaced [RGraph](http://www.rgraph.net/) with [Highcharts JS](http://www.highcharts.com/) as the JavaScript chart library used for the HTML report's code coverage dashboard.
