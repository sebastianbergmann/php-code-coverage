# ChangeLog

All notable changes are documented in this file using the [Keep a CHANGELOG](http://keepachangelog.com/) principles.

## [14.0.0] - 2026-MM-DD

### Added

* `SebastianBergmann\CodeCoverage\Serializer` class for serializing `SebastianBergmann\CodeCoverage\CodeCoverage` objects in a versioned format to a file
* `SebastianBergmann\CodeCoverage\Unserializer` class for unserializing `SebastianBergmann\CodeCoverage\CodeCoverage` objects from a file creating using `SebastianBergmann\CodeCoverage\Serializer`

### Changed

* The format of the file written by `SebastianBergmann\CodeCoverage\Serializer` is incompatible with the format of the file that written by `SebastianBergmann\CodeCoverage\Report\PHP` in the past

### Removed

* The `SebastianBergmann\CodeCoverage\Report\PHP` class was removed, use the new `SebastianBergmann\CodeCoverage\Serializer` class instead

[14.0.0]: https://github.com/sebastianbergmann/php-code-coverage/compare/13.0...14.0.0
