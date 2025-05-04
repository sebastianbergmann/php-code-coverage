# ChangeLog

All notable changes are documented in this file using the [Keep a CHANGELOG](http://keepachangelog.com/) principles.

## [12.2.1] - 2025-05-04

### Changed

* Safeguard `file_get_contents()` call with `is_file()` call to avoid problems with error handlers that act on suppressed warnings

## [12.2.0] - 2025-05-03

### Changed

* [#1074](https://github.com/sebastianbergmann/php-code-coverage/issues/1074): Use more efficient `AttributeParentConnectingVisitor`
* [#1076](https://github.com/sebastianbergmann/php-code-coverage/issues/1076): Replace unmaintained JavaScript library for charts with billboard.js
* Reduced number of I/O and hashing operations when using the static analysis cache
* Use SHA-256 instead of MD5 to generate cache keys for static analysis cache (as SHA-256 is significantly faster than MD5 with PHP >= 8.4 on modern CPUs)

[12.2.1]: https://github.com/sebastianbergmann/php-code-coverage/compare/12.2.0...12.2.1
[12.2.0]: https://github.com/sebastianbergmann/php-code-coverage/compare/12.1.2...12.2.0
