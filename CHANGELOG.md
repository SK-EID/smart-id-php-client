# Changelog
All notable changes to this project will be documented in this file.
The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

## [2.2.1] - 2021-10-13

### Fixed
- Now can be run with both PHP 7.4 and PHP 8

## [2.2] - 2021-09-27

### Fixed
- Fix user agent header regex validation

### Added 
- Library and PHP version number added to User-Agent header of all requests

## [2.1] - 2021-09-24

### Changed
- PHP version upgraded 7.2 -> 7.3
- PhpUnit upgraded to 5.7 -> 9

### Added 
- New function AuthenticationIdentity->getDateOfBirth() that reads birthdate info from a separate field in certificate or detects it from national identity number.
NB This function can return null for some Latvian certificates.
- New function AuthenticationIdentity->getIdentityNumber() that returns personal identification number without a PNOEE-, IDXLV- etc prefix
- Return types added to methods
- Library and PHP version number added to User-Agent header of outgoing requests

## [2.0] - 2021-04-07

### Added
- Support for Smart id api version 2.0
- Authentication routes using semantics identifiers
- Different Interaction types based on enduser device capabilities

### Removed
- Building request with national identity (Use [semantics identifiiers](https://www.etsi.org/deliver/etsi_en/319400_319499/31941201/01.01.01_60/en_31941201v010101p.pdf) (chapter 5.1.3))
- Hard coded ssl public keys for demo and live smart id environments (Since this release, relying parties need to specify the public keys used when setting up the client)

### Changed
- php version >= 7.2 

## [1.5.2] - 2021-02-08

### Fixed
- Demo SSL key pinning [#24](https://github.com/SK-EID/smart-id-php-client/issues/24)

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
