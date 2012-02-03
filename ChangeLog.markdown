PHP_CodeCoverage 1.2
====================

This is the list of changes for the PHP_CodeCoverage 1.2 release series.

PHP_CodeCoverage 1.2.0
----------------------

* The new `@coversDefaultClass` annotation enables short `@covers` annotations when working with long class names or namespaces.
* When `processUncoveredFilesFromWhitelist=FALSE` is set then files that are whitelisted and uncovered are now included in the code coverage but with all lines, including those that are not executable, counted as not executed.
* The [Finder](http://symfony.com/doc/2.0/components/finder.html) component of the Symfony project is now used to find files.
* PHP_CodeCoverage 1.2 is only supported on PHP 5.3.3 (or later) and PHP 5.4.0 (or later) is highly recommended.
