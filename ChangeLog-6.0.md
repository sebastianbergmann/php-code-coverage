# Changes in PHP_CodeCoverage 6.0

All notable changes of the PHP_CodeCoverage 6.0 release series are documented in this file using the [Keep a CHANGELOG](http://keepachangelog.com/) principles.

## [6.0.3] - 2018-04-06

### Fixed

* Fixed [#602](https://github.com/sebastianbergmann/php-code-coverage/pull/602): Regression introduced in version 6.0.2

## [6.0.2] - 2018-04-06

### Changed

* `Clover`, `Crap4j`, and `PHP` report writers now raise an exception when their call to `file_put_contents()` fails

## [6.0.1] - 2018-02-02

* Fixed [#584](https://github.com/sebastianbergmann/php-code-coverage/issues/584): Target directories are not created recursively

## [6.0.0] - 2018-02-01

### Changed

* Almost all classes are now final

### Fixed

* Fixed [#409](https://github.com/sebastianbergmann/php-code-coverage/issues/409): Merging of code coverage information does not work correctly

### Removed

* Implemented [#561](https://github.com/sebastianbergmann/php-code-coverage/issues/561): Remove HHVM driver
* Implemented [#562](https://github.com/sebastianbergmann/php-code-coverage/issues/562): Remove code specific to Hack language constructs
* Implemented [#563](https://github.com/sebastianbergmann/php-code-coverage/issues/563): Drop support for PHP 7.0

[6.0.3]: https://github.com/sebastianbergmann/php-code-coverage/compare/6.0.2...6.0.3
[6.0.2]: https://github.com/sebastianbergmann/php-code-coverage/compare/6.0.1...6.0.2
[6.0.1]: https://github.com/sebastianbergmann/php-code-coverage/compare/6.0.0...6.0.1
[6.0.0]: https://github.com/sebastianbergmann/php-code-coverage/compare/5.2...6.0.0

