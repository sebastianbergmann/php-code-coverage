# ChangeLog

All notable changes are documented in this file using the [Keep a CHANGELOG](http://keepachangelog.com/) principles.

## [12.3.0] - 2025-05-23

### Changed

* [#1080](https://github.com/sebastianbergmann/php-code-coverage/pull/1080): Support for reporting code coverage information in OpenClover XML reporter; unlike the existing Clover XML reporter, which remains unchanged, this new reporter validates against the OpenClover project's XML schema definition, with one exception: we do not generate `<testproject>` element. This feature is experimental and the generated XML might change in order to improve compliance with the OpenClover project's XML schema definition further. Such changes will be made in bugfix and/or minor releases even if they break backward compatibility.

[12.3.0]: https://github.com/sebastianbergmann/php-code-coverage/compare/12.2.1...12.3.0
