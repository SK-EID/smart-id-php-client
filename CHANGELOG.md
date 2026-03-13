# Changelog
All notable changes to this project will be documented in this file.
The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

## [3.0.0] - 2026-03-13

Complete rewrite of the SDK to support **Smart-ID RP API v3**. This release is not backwards compatible with 2.x.

### Added
- Smart-ID RP API v3 support
- Device link authentication flows with QR code and Web2App URL generation
- `SmartIdClient` facade as the main entry point (replaces setter-based `Client`)
- `DeviceLinkAuthenticationRequestBuilder` and `NotificationAuthenticationRequestBuilder` (replace single `AuthenticationRequestBuilder`)
- `DeviceLinkInteraction` and `NotificationInteraction` models with `confirmationMessage`, `displayTextAndPin`, and `confirmationMessageAndVerificationCodeChoice` interaction types (replace generic `Interaction`)
- ACSP_V2 signature protocol with RSA-PSS verification and `signatureAlgorithmParameters` validation
- SHA3-256, SHA3-384, SHA3-512 hash algorithm support (in addition to existing SHA-256, SHA-384, SHA-512)
- `RpChallengeGenerator` for generating RP challenges (replaces client-side `AuthenticationHash`/`SignableData` approach)
- `DeviceLinkBuilder` for generating time-based QR code URLs and Web2App deep links
- `CallbackUrlUtil` and `CallbackUrlValidator` for Web2App/App2App callback URL creation and validation
- `TrustedCACertificateStore` for flexible CA certificate management — load from defaults, directory, or manually (replaces directory-only approach in `AuthenticationResponseValidator`)
- `SslPinnedPublicKeyStore` with SPKI hash-based (`sha256//...`) HTTPS public key pinning via `CURLOPT_PINNEDPUBLICKEY` — supports `addPublicKeyHash()`, `fromString()`, `fromArray()`, `loadFromDirectory()`, `loadDemo()` (replaces raw key string via `setPublicSslKeys()`)
- `UserAccountException` with `isNoSuitableAccount()`, `isPersonShouldViewApp()`, `isClientTooOld()` for HTTP 471/472/480 responses
- `ProtocolFailureException`, `ServerErrorException`, `ValidationException`, `UnauthorizedException` exceptions
- `UserRefusedInteractionException` that extends `UserRefusedException` with `getInteraction()` for identifying which interaction was cancelled
- Automatic end result to exception mapping in `SessionStatusPoller` (replaces `SmartIdAuthenticationResult` error codes approach)
- Typed PHP 8.4 enums: `CertificateLevel`, `HashAlgorithm`, `InteractionType`, `SchemeName`, `FlowType`, `SignatureProtocol`, `DeviceLinkType`, `SessionType`
- `ext-curl` as an explicit composer dependency
- Bundled demo SSL keys via `SslPinnedPublicKeyStore::loadDemo()`
- Bundled production and test trusted CA certificates via `TrustedCACertificateStore`

### Changed
- Minimum PHP version upgraded from 7.4 to 8.4
- PHPUnit upgraded from 9.x to 12.5.x
- Switched from PSR-0 to PSR-4 autoloading
- `SmartIdRestConnector` rewritten — SSL pinning is now handled via `SslPinnedPublicKeyStore` instead of raw key strings; API endpoints updated for v3
- `AuthenticationResponseValidator` rewritten — now validates certificate trust chain, basic constraints, key usage, extended key usage, certificate policy OIDs, and ACSP_V2 RSA-PSS signatures (v2 only verified `openssl_verify` with `OPENSSL_ALGO_SHA512` and `openssl_x509_checkpurpose`)
- `AuthenticationIdentity` moved from `Api\Data` to `Model` namespace — now provides `getFullName()`, `getGender()`, `getAge()`, and improved `getDateOfBirth()` for Baltic states
- `SemanticsIdentifier` moved from `Api\Data` to `Model` namespace — simplified with `forPerson()` and `fromString()` factory methods (replaces `SemanticsIdentifierBuilder` and `SemanticsIdentifierTypes`)
- `SessionStatusPoller` moved from `Api` to `Session` namespace — now uses long polling with configurable timeout (replaces `pollingSleepTimeoutMs` sleep-based approach)
- Session status response models (`SessionStatus`, `SessionResult`, `SessionSignature`, `SessionCertificate`) moved from `Api\Data` to `Session` namespace with v3 fields (`signatureAlgorithmParameters`, `flowType`, `userChallenge`, `serverRandom`, `deviceIpAddress`)
- Exception hierarchy simplified — granular per-interaction exceptions (`UserRefusedCertChoiceException`, `UserRefusedDisplayTextAndPinException`, `UserRefusedConfirmationMessageException`, `UserRefusedConfirmationMessageWithVcChoiceException`, `UserRefusedVcChoiceException`) consolidated into `UserRefusedException` and `UserRefusedInteractionException`
- `WrongVerificationCodeException` renamed from `UserSelectedWrongVerificationCodeException`
- `RequiredInteractionNotSupportedException` renamed from `RequiredInteractionNotSupportedByAppException`

### Removed
- Smart-ID RP API v2 support
- `Client` class (replaced by `SmartIdClient` facade)
- `AuthenticationRequestBuilder`, `SmartIdRequestBuilder`, `Authentication`, `AbstractApi`, `ApiInterface`, `ApiType` classes
- `AuthenticationHash`, `SignableData`, `DigestCalculator` classes (RP challenge is now generated server-side via `RpChallengeGenerator`)
- `SmartIdAuthenticationResponse`, `SmartIdAuthenticationResult`, `SmartIdAuthenticationResultError` classes (validation errors are now thrown as exceptions)
- `SessionStatusFetcher`, `SessionStatusFetcherBuilder` classes (replaced by `SessionStatusPoller`)
- `AuthenticationCertificate`, `AuthenticationCertificateSubject`, `AuthenticationCertificateIssuer`, `AuthenticationCertificateExtensions`, `CertificateParser` classes
- `AuthenticationSessionRequest`, `AuthenticationSessionResponse`, `SessionStatusRequest` classes (replaced by typed request/response objects per flow)
- `PropertyMapper`, `SessionEndResultCode`, `SessionStatusCode`, `CertificateLevelCode`, `HashType` classes (replaced by typed enums)
- `SemanticsIdentifierBuilder`, `SemanticsIdentifierTypes` classes
- `Interaction` class (replaced by `DeviceLinkInteraction` and `NotificationInteraction`)
- `EnduringSmartIdException`, `UserActionException` intermediate exception classes
- `UserAccountNotFoundException`, `NotFoundException` exceptions (consolidated into `UserAccountException` and `UnauthorizedException`)
- `ServerMaintenanceException` (replaced by `ServerErrorException`)
- `UnprocessableSmartIdResponseException` (replaced by `TechnicalErrorException`)
- `CertificateAttributeUtil`, `NationalIdentityNumberUtil`, `Curl` utility classes
- `VerificationCodeCalculator` from `Api\Data` namespace (replaced by `Util\VerificationCodeCalculator`)

## [2.3.3] - 2025-04-07
### Fixed
- return NULL for getDateOfBirth instead of error

## [2.3.2] - 2025-01-17
### Added
- new CA certificates

## [2.3.1] - 2022-08-23
### Fixed
- function parseLvDateOfBirth

## [2.3] - 2022-01-06

### Changed
- If user picks wrong verification code then UserSelectedWrongVerificationCodeException is thrown (previously TechnicalErrorException was thrown)
- Minimal PHP version lifted to 7.4

### Added
- Added intermediate exceptions (UserAccountException if there is problem with user Smart-ID account, UserActionException if the exception is caused by user's actions, EnduringSmartIdException if something is wrong with Smart-ID service or the integration configuration is invalid) so there is no need to handle each case independently.
- New method SmartIdAuthenticationResponse::getDocumentNumber()
- Usage examples added to README.md

## [2.2.2] - 2022-01-03

### Fixed
- Latvian ID code validation

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
