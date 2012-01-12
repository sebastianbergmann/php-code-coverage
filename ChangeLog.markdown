PHP_CodeCoverage 1.2
====================

This is the list of changes for the PHP_CodeCoverage 1.2 release series.

PHP_CodeCoverage 1.2.0
----------------------

* The new `@coversDefaultClass` annotation enables short `@covers` annotations when working with long class names or namespaces.
* Files that are whitelisted and uncovered are no longer compiled and evaluated to prevent issues. All lines, including those that are not executable, of such a file are counted as not executed.
* PHP_CodeCoverage 1.2 is only supported on PHP 5.3.3 (or later) and PHP 5.4.0 (or later) is highly recommended.
