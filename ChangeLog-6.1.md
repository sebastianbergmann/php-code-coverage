# Changes in php-code-coverage 6.1

All notable changes of the php-code-coverage 6.1 release series are documented in this file using the [Keep a CHANGELOG](http://keepachangelog.com/) principles.

## [6.1.4] - 2018-10-31

### Fixed

* Fixed [#650](https://github.com/sebastianbergmann/php-code-coverage/issues/650): Wasted screen space in HTML code coverage report

## [6.1.3] - 2018-10-23

### Changed

* Use `^3.1` of `sebastian/environment` again due to [regression](https://github.com/sebastianbergmann/environment/issues/31)

## [6.1.2] - 2018-10-23

### Fixed

* Fixed [#645](https://github.com/sebastianbergmann/php-code-coverage/pull/645): Crash that can occur when php-token-stream parses invalid files

## [6.1.1] - 2018-10-18

### Changed

* This component now allows `^4` of `sebastian/environment`

## [6.1.0] - 2018-10-16

### Changed

* Class names are now abbreviated (unqualified name shown, fully qualified name shown on hover) in the file view of the HTML report
* Update HTML report to Bootstrap 4

[6.1.4]: https://github.com/sebastianbergmann/php-code-coverage/compare/6.1.3...6.1.4
[6.1.3]: https://github.com/sebastianbergmann/php-code-coverage/compare/6.1.2...6.1.3
[6.1.2]: https://github.com/sebastianbergmann/php-code-coverage/compare/6.1.1...6.1.2
[6.1.1]: https://github.com/sebastianbergmann/php-code-coverage/compare/6.1.0...6.1.1
[6.1.0]: https://github.com/sebastianbergmann/php-code-coverage/compare/6.0...6.1.0

