# ChangeLog

All notable changes are documented in this file using the [Keep a CHANGELOG](http://keepachangelog.com/) principles.

## [12.2.0] - 2025-MM-DD

### Changed

* Use SHA-256 instead of MD5 to generate cache keys for static analysis cache (as SHA-256 is significantly faster than MD5 with PHP >= 8.4 on modern CPUs)

[12.2.0]: https://github.com/sebastianbergmann/php-code-coverage/compare/12.1.2...main
