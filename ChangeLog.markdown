PHP_CodeCoverage 1.2
====================

This is the list of changes for the PHP_CodeCoverage 1.2 release series.

PHP_CodeCoverage 1.2.9
----------------------

* Improved rendering of "lines covered" numbers for functions and methods with long names or argument lists.
* Fixed the title of the Y axis of the Code Coverage ./. Cyclomatic Complexity dashboard chart.
* Upgraded to Highcharts 2.3.5.
* Upgraded to jQuery 1.9.1.

PHP_CodeCoverage 1.2.8
----------------------

* Reduced vertical whitespace in sourcecode view.
* Upgraded to Bootstrap 2.2.2.

PHP_CodeCoverage 1.2.7
----------------------

* The `html5shiv.js` is now bundled.
* Fixed sebastianbergmann/phpunit#702: `@coversNothing` didn't work as documented.

PHP_CodeCoverage 1.2.6
----------------------

* Fixed #126: `E_NOTICE` thrown when generating coverage report.

PHP_CodeCoverage 1.2.5
----------------------

* Fixed regression introduced in PHP_CodeCoverage 1.2.4.

PHP_CodeCoverage 1.2.4
----------------------

* Fixed #123: Incorrect code coverage for interfaces.

PHP_CodeCoverage 1.2.3
----------------------

* Implemented #116: Do not rely on autoloader class map to populate blacklist.
* Added support for parentheses after method names in the `@covers` annotation.
* When `addUncoveredFilesFromWhitelist=FALSE` is set then files that are whitelisted but not covered by a single test are now excluded from the code coverage.
* Fixed #81: Non-english locales broke the coverage bars in the HTML report.
* Fixed #118: Percentage for tested classes and traits displayed incorrectly.
* Fixed #121: One line `@covers` annotations did not work.

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
* When `processUncoveredFilesFromWhitelist=FALSE` is set then files that are whitelisted but not covered by a single test are now included in the code coverage but with all lines, including those that are not executable, counted as not executed.
* PHP_CodeCoverage 1.2 is only supported on PHP 5.3.3 (or later) and PHP 5.4.7 (or later) is highly recommended.
