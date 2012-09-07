PHP_CodeCoverage 1.2
====================

This is the list of changes for the PHP_CodeCoverage 1.2 release series.

PHP_CodeCoverage 1.2.0
----------------------

* The new `@coversDefaultClass` annotation enables short `@covers` annotations when working with long class names or namespaces.
* The new `@coversNothing` annotation can be used so tests do not record any code coverage. Useful for integration testing. 
* When `processUncoveredFilesFromWhitelist=FALSE` is set then files that are whitelisted and uncovered are now included in the code coverage but with all lines, including those that are not executable, counted as not executed.
* PHP_CodeCoverage 1.2 is only supported on PHP 5.3.3 (or later) and PHP 5.4.0 (or later) is highly recommended.
