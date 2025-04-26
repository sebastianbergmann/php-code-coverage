# ChangeLog

All notable changes are documented in this file using the [Keep a CHANGELOG](http://keepachangelog.com/) principles.

## [12.1.3] - 2025-MM-DD

### Changed

* Use SHA-256 instead of MD5 to generate cache keys for static analysis cache (as SHA-256 is significantly faster than MD5 with PHP >= 8.4 on modern CPUs)

## [12.1.2] - 2025-04-03

### Fixed

* [#1069](https://github.com/sebastianbergmann/php-code-coverage/issues/1069): Check for unintentionally covered code is wrong

## [12.1.1] - 2025-04-03

### Fixed

* Child classes of child classes are not considered for `ClassesThatExtendClass` target

## [12.1.0] - 2025-03-17

### Changed

* `CacheWarmer::warmCache()` now returns the number of cache hits and cache misses

[12.1.2]: https://github.com/sebastianbergmann/php-code-coverage/compare/12.1.1...12.1.2
[12.1.1]: https://github.com/sebastianbergmann/php-code-coverage/compare/12.1.0...12.1.1
[12.1.0]: https://github.com/sebastianbergmann/php-code-coverage/compare/12.0.5...12.1.0
