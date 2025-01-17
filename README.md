[![Build Status](https://app.travis-ci.com/SK-EID/smart-id-php-client.svg?branch=master)](https://app.travis-ci.com/SK-EID/smart-id-php-client)
[![Latest Version](https://img.shields.io/packagist/v/sk-id-solutions/smart-id-php-client?label=version)](https://packagist.org/packages/sk-id-solutions/smart-id-php-client/)
[![License: LGPL v3](https://img.shields.io/badge/License-MIT-green.svg)](https://opensource.org/licenses/MIT)

# Smart-ID PHP client

## Introduction
The Smart-ID PHP client can be used for easy integration of the Smart-ID solution to information systems or e-services.

## Features
* Simple interface for user authentication

Smart-ID PHP client works with PHP 7.4 and PHP 8+

**This PHP client cannot be used to create digitally signed containers because PHP does not have a library like DigiDoc4J.**

## Installation
The recommended way to install Smart-ID PHP Client is through [Composer]:

```
composer require sk-id-solutions/smart-id-php-client "2.3.2"
```

See [packagist](https://packagist.org/packages/sk-id-solutions/smart-id-php-client) for latest published version
and [changelog](CHANGELOG.md) for details.

# How to use it

## Configure client details and https pinning

   Used to prevent man-in-the-middle attacks. [More on man in the middle attacks in case of using smart id.](https://github.com/SK-EID/smart-id-documentation#226-rp-api-endpoint-authentication)

   Setting the client to trust specific public keys. Production SSL certificates used can be found [here](https://www.skidsolutions.eu/en/repository/certs/)
and demo environment certificates are [here](https://www.skidsolutions.eu/en/repository/certs/certificates-for-testing).
   
   The setPublicSslKeys method requires a string of sha256 hashes of the public keys used delimited with ";". You can extract hashes from certificates using next openssl command.
   
   ```
   openssl x509 -inform PEM -in certificate.pem -noout -pubkey | openssl rsa -pubin -outform der 2>/dev/null | openssl dgst -sha256 -binary | openssl enc -base64
   ```
   
   The supplied string should be of format sha256//sha256-hash-of-the-public-key;

<!-- NB! Do not change code samples here but instead copy from ReadmeTest.setUp() -->
```PHP
$this->client = new Client();
$this->client
    ->setRelyingPartyUUID( '00000000-0000-0000-0000-000000000000' ) // In production replace with your UUID
    ->setRelyingPartyName( 'DEMO' ) // In production replace with your name
    ->setHostUrl( 'https://sid.demo.sk.ee/smart-id-rp/v2/' ) // In production replace with production service URL
        // in production replace with correct server SSL key
    ->setPublicSslKeys("sha256//Ps1Im3KeB0Q4AlR+/J9KFd/MOznaARdwo4gURPCLaVA=");
 ```

## Authenticating with semantics identifier

Following example also demonstrates how to validate authentication result and how to handle exceptions.

<!-- NB! Do not change code samples here but instead copy from ReadmeTest.authenticateWithSemanticsIdentifier() -->
```PHP
$semanticsIdentifier = SemanticsIdentifier::builder()
    ->withSemanticsIdentifierType('PNO')
    ->withCountryCode('LT')
    ->withIdentifier('30303039914')
    ->build();

// For security reasons a new hash value must be created for each new authentication request
$authenticationHash = AuthenticationHash::generate();

$verificationCode = $authenticationHash->calculateVerificationCode();

// display verification code to the user
echo "Verification code: " . $verificationCode . "\n";

$authenticationResponse = null;
try
{
  $authenticationResponse = $this->client->authentication()
      ->createAuthentication()
      ->withSemanticsIdentifier( $semanticsIdentifier ) 
      ->withAuthenticationHash( $authenticationHash )
      ->withCertificateLevel( CertificateLevelCode::QUALIFIED ) // Certificate level can either be "QUALIFIED" or "ADVANCED"
      ->withAllowedInteractionsOrder((array(
          Interaction::ofTypeVerificationCodeChoice("Enter awesome portal?"),
          Interaction::ofTypeDisplayTextAndPIN("Enter awesome portal?"))))
      ->authenticate(); // this blocks until user has responded
}
catch (UserRefusedException $e) {
  throw new RuntimeException("You pressed cancel in Smart-ID app.");
}
catch (UserSelectedWrongVerificationCodeException $e) {
  throw new RuntimeException("You selected wrong verification code in Smart-ID app. Please try again. ");
}
catch (SessionTimeoutException $e) {
  throw new RuntimeException("Session timed out (you didn't enter PIN1 in Smart-ID app).");
}
catch (UserAccountNotFoundException $e) {
  throw new RuntimeException("User does not have a Smart-ID account");
}
catch (UserAccountException $e) {
  throw new RuntimeException("Unable to authenticate due to a problem with your Smart-ID account.");
}
catch (EnduringSmartIdException $e) {
  throw new RuntimeException("Problem with connecting to Smart-ID service. Please try again later.");
}
catch (SmartIdException $e) {
  throw new RuntimeException("Smart-ID authentication process failed for uncertain reason: ". $e);
}

// create a folder with name "trusted_certificates" and set path to that folder here:
$pathToFolderWithTrustedCertificates = __DIR__ . '/../../../resources';

$authenticationResponseValidator = new AuthenticationResponseValidator($pathToFolderWithTrustedCertificates);
$authenticationResult = $authenticationResponseValidator->validate( $authenticationResponse );


if ($authenticationResult->isValid()) {
  echo "Hooray! Authentication result is valid";
}
else {
   throw new RuntimeException("Error! Response is not valid! Error(s): ". implode(",", $authenticationResult->getErrors()));
}



$authenticationIdentity = $authenticationResult->getAuthenticationIdentity();

echo "hello name: " . $authenticationIdentity->getGivenName() . ' ' . $authenticationIdentity->getSurName() . "\n";
echo "from " . $authenticationIdentity->getCountry() . "\n";
echo "born " . $authenticationIdentity->getDateOfBirth()->format("D d F o") . "\n";

// you might need this if you want to start authentication with document number
echo "Authenticated user documentNumber is: ".$authenticationResponse->getDocumentNumber(). "\n";
 ```

### Validate authentication result

To validate the authentication result (that it was signed by Smart-ID and not some man-in-the-middle or
accidentally connecting to demo environment from production).
You need to create directory trusted_certificates and place smart-id certificates in there.
You can get the needed certificates from links that are described in the "https pinning" 
chapter above.

Example path to resource directory: $resourceLocation = '/path/to/resource'; 
where it will look for directory named trusted_certificates and read certs from there.
If no path is specified it will take trusted certs, that are provided by client itself.
They are located at src/resources/trusted_certificates.

#### Note about verification code and validating the signature

This what happens behind the scenes (all the steps besides step #5 are performed by this library):

1. For every new authentication the library generates a random value (stored into variable 'dataToSign')
2. A digest (SHA-512, SHA-384 or SHA-256) is calculated out of this random value  (stored into variable 'hash')
3. Verification code that is displayed to the end user is calculated out of this digest.
4. The authentication request (together with value of 'hash') is sent out to the server.
5. Now signing process takes place in user's the phone and the Smart-ID REST service returns the signature and the authentication certificate of the user.
6. The library verifies that the signature value that was returned is really a valid signature.
   (For the verification it uses the value of 'dataToSign' (and not the digest that is stored in 'hash') together with the authentication signature.)


### Extract date of birth of the authenticated person

All Estonian and Lithuanian national identity numbers contain date-of-birth info
ant his is why getDateOfBirth() function always returns a correct value for them.
Also birthdate info is present within old type of Latvian national identity numbers.

 ```
echo "born " . $authenticationIdentity->getDateOfBirth()->format("D d F o") . "\n";
 ```

For persons with new type of Latvian national identity number the date-of-birth is parsed from
a separate field of the certificate but for some older Smart-id accounts
(issued between 2017-07-01 and 2021-05-20) the value might be missing.

More info about the availability of this separate field in the certificates:
https://github.com/SK-EID/smart-id-documentation/wiki/FAQ#where-can-i-find-users-date-of-birth

## Authenticating with document number

It might be needed to use document number instead of semantics identifier
when you are (for some reason) re-authenticating the user in a short period of time
and you want the user to use the same device as previously.

If user has several Smart-ID accounts (for example one in phone and one in tablet)
then when authenticating with semantics identifier both of the devices initiate the
flow (user can pick either one of the devices and type in PIN there).
Since document number is device-specific then when you use document
number only one of user devices starts the authentication flow.

You get the documentNumber of the user after successful authentication.
See the example above where documentNumber is logged out in the end.

<!-- NB! Do not change code samples here but instead copy from ReadmeTest.authenticateWithDocumentNumber() -->
```PHP
$authenticationResponse = $this->client->authentication()
  ->createAuthentication()
  ->withDocumentNumber( 'PNOLT-30303039914-MOCK-Q' )
  ->withAuthenticationHash( $authenticationHash )
  ->withCertificateLevel( CertificateLevelCode::QUALIFIED ) // Certificate level can either be "QUALIFIED" or "ADVANCED"
  ->withAllowedInteractionsOrder((array(
      Interaction::ofTypeVerificationCodeChoice("Enter awesome portal?"),
      Interaction::ofTypeDisplayTextAndPIN("Enter awesome portal?"))))
  ->authenticate(); // this blocks until user has responded
```

## Authenticate with polling every 5 seconds

Previous examples block until the user has typed in PIN code or pressed cancel or authentication has failed for some other reason (like timeout).
This example demonstrates polling the status every 5 seconds.

<!-- NB! Do not change code samples here but instead copy from ReadmeTest.authenticateWithPolling() -->
```PHP

$sessionId = null;
try
{
  $sessionId = $this->client->authentication()
      ->createAuthentication()
      ->withSemanticsIdentifier( $semanticsIdentifier ) // or with document number: ->withDocumentNumber( 'PNOEE-10101010005-Z1B2-Q' )
      ->withAuthenticationHash( $authenticationHash )
      ->withCertificateLevel( CertificateLevelCode::QUALIFIED ) // Certificate level can either be "QUALIFIED" or "ADVANCED"
      ->withAllowedInteractionsOrder((array(
          Interaction::ofTypeVerificationCodeChoice("Ready to poll?"),
          Interaction::ofTypeDisplayTextAndPIN("Ready to poll status repeatedly?"))))
      ->startAuthenticationAndReturnSessionId();
}
catch (SmartIdException $e) {
  // Handle exception (more on exceptions in "Handling intentional exceptions")
  throw new RuntimeException("Authentication failed. NB! Use exception handling blocks from above example: ". $e);
}

$authenticationResponse = null;
try
{
  for ( $i = 0; $i <= 10; $i++ )
  {
    $authenticationResponse = $this->client->authentication()
        ->createSessionStatusFetcher()
        ->withSessionId( $sessionId )
        ->withAuthenticationHash( $authenticationHash )
        ->withSessionStatusResponseSocketTimeoutMs( 10000 )
        ->getAuthenticationResponse();

    if ( !$authenticationResponse->isRunningState() )
    {
      break;
    }
    sleep( 5 );
  }
}
catch (SmartIdException $e) {
  throw new RuntimeException("Authentication failed. NB! Use exception handling blocks from above example.". $e);
}

// validate authentication result, get authentication person details
```

[Composer]: http://getcomposer.org
