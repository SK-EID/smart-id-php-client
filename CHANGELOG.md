# Changelog
All notable changes to this project will be documented in this file.
The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

## [1.5.1] - 2019-11-25

### Fixed
- Poller did not use https pinning configuration from client [#22](https://github.com/SK-EID/smart-id-php-client/pull/22)

## [1.5] - 2019-11-25

### Added
- Http public key pinning [#18](https://github.com/SK-EID/smart-id-php-client/pull/18)

### Fixed
- Poller did not use specified network interface [#4](https://github.com/SK-EID/smart-id-php-client/issues/4)
- Add exception message when user is not found [#16](https://github.com/SK-EID/smart-id-php-client/pull/16)

### Changed
- php version 5.6 to 7.0.7

## [2.0] - 2020-08-28

### Added
- Support for Smart id api version 2.0
- Authentication routes using semantics identifiers
- Different Interaction types based on enduser device capabilities

### Removed
- Building request with national identity (Use [semantics identifiiers](https://www.etsi.org/deliver/etsi_en/319400_319499/31941201/01.01.01_60/en_31941201v010101p.pdf) (chapter 5.1.3))

### Changed
- php version >= 7.1 