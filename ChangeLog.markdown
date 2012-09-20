PHP_CodeCoverage 1.2
====================

This is the list of changes for the PHP_CodeCoverage 1.2 release series.

PHP_CodeCoverage 1.2.2
----------------------

* Fixed #115: Backwards compatibility wrapper for `trait_exists()` does not work.

PHP_CodeCoverage 1.2.1
----------------------

* Fixed invalid markup in the HTML report.
* The version number is now displayed when using PHP_CodeCoverage from a Composer install or Git checkout.

PHP_CodeCoverage 1.2.0
----------------------

* The HTML report has been redesigned.
* The new `@coversDefaultClass` annotation enables short `@covers` annotations when working with long class names or namespaces.
* The new `@coversNothing` annotation can be used so tests do not record any code coverage. Useful for integration testing. 
* When `processUncoveredFilesFromWhitelist=FALSE` is set then files that are whitelisted and uncovered are now included in the code coverage but with all lines, including those that are not executable, counted as not executed.
* PHP_CodeCoverage 1.2 is only supported on PHP 5.3.3 (or later) and PHP 5.4.7 (or later) is highly recommended.
