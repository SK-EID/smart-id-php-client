<?php

namespace Sk\SmartId\Tests;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Sk\SmartId\Api\AuthenticationResponseValidator;
use Sk\SmartId\Api\Data\AuthenticationHash;
use Sk\SmartId\Api\Data\CertificateLevelCode;
use Sk\SmartId\Api\Data\Interaction;
use Sk\SmartId\Api\Data\SemanticsIdentifier;
use Sk\SmartId\Client;
use Sk\SmartId\Exception\EnduringSmartIdException;
use Sk\SmartId\Exception\SessionTimeoutException;
use Sk\SmartId\Exception\SmartIdException;
use Sk\SmartId\Exception\UserAccountException;
use Sk\SmartId\Exception\UserAccountNotFoundException;
use Sk\SmartId\Exception\UserRefusedException;
use Sk\SmartId\Exception\UserSelectedWrongVerificationCodeException;

/**
 * These tests contain snippets used in Readme.md
 * This is needed to guarantee that tests compile.
 * If anything changes in this class (except setUp method) the changes must be reflected in Readme.md
 * These are not real tests!
 */
class ReadmeTest extends TestCase
{

//Make it available for your application


  private Client $client;

  public function setUp() : void
  {
    $this->client = new Client();
    $this->client
        ->setRelyingPartyUUID( '00000000-0000-0000-0000-000000000000' ) // In production replace with your UUID
        ->setRelyingPartyName( 'DEMO' ) // In production replace with your name
        ->setHostUrl( 'https://sid.demo.sk.ee/smart-id-rp/v2/' ) // In production replace with production service URL
            // in production replace with correct server SSL key
        ->setPublicSslKeys("sha256//nTL2Ju/1Mt+WAHeejqZHtgPNRu049iUcXOPq0GmRgJg=;sha256//wkdgNtKpKzMtH/zoLkgeScp1Ux4TLm3sUldobVGA/g4=");
  }

  /**
   * @test
   */
  public function authenticateWithSemanticsIdentifier()
  {
    // Start copy to README.md

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
      throw new RuntimeException("Smart-ID authentication process failed for uncertain reason.", $e);
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


    // END COPYING to README.md starting this line.

    self::assertEquals('QUALIFIED OK', $authenticationIdentity->getGivenName() );
    self::assertEquals('TESTNUMBER', $authenticationIdentity->getSurName() );
    self::assertEquals('PNOLT-30303039914-PBZK-Q', $authenticationResponse->getDocumentNumber() );
  }

  /**
   * @test
   */
  public function authenticateWithDocumentNumber()
  {
    $authenticationHash = AuthenticationHash::generate();
    $verificationCode = $authenticationHash->calculateVerificationCode();
    $authenticationResponse = null;

    try
    {
      // START COPY

      $authenticationResponse = $this->client->authentication()
          ->createAuthentication()
          ->withDocumentNumber( 'PNOLV-329999-99901-AAAA-Q' )
          ->withAuthenticationHash( $authenticationHash )
          ->withCertificateLevel( CertificateLevelCode::QUALIFIED ) // Certificate level can either be "QUALIFIED" or "ADVANCED"
          ->withAllowedInteractionsOrder((array(
              Interaction::ofTypeVerificationCodeChoice("Enter awesome portal?"),
              Interaction::ofTypeDisplayTextAndPIN("Enter awesome portal?"))))
          ->authenticate(); // this blocks until user has responded

      // END COPY

    }
    catch (SmartIdException $e) {
      throw new RuntimeException("Smart-ID authentication process failed for uncertain reason.", $e);
    }

    // create a folder with name "trusted_certificates" and set path to that folder here:
    $pathToFolderWithTrustedCertificates = __DIR__ . '/../../../resources';

    $authenticationResponseValidator = new AuthenticationResponseValidator($pathToFolderWithTrustedCertificates);
    $authenticationResult = $authenticationResponseValidator->validate( $authenticationResponse );

    if (!$authenticationResult->isValid()) {
      throw new RuntimeException("Error! Response is not valid! Error(s): ". implode(",", $authenticationResult->getErrors()));
    }

    $authenticationIdentity = $authenticationResult->getAuthenticationIdentity();

    // DO NOT INCLUDE CODE BELOW IN README

    self::assertEquals('BOD', $authenticationIdentity->getGivenName() );
    self::assertEquals('TESTNUMBER', $authenticationIdentity->getSurName() );
    self::assertEquals('LV', $authenticationIdentity->getCountry() );
    self::assertEquals('03.03.1903', $authenticationIdentity->getDateOfBirth()->format("d.m.o"));

  }

  /**
   * @test
   */
  public function authenticateWithPolling()
  {

    $semanticsIdentifier = SemanticsIdentifier::builder()
        ->withSemanticsIdentifierType('PNO')
        ->withCountryCode('LT')
        ->withIdentifier('30303039914')
        ->build();

// For security reasons a new hash value must be created for each new authentication request
    $authenticationHash = AuthenticationHash::generate();

    $verificationCode = $authenticationHash->calculateVerificationCode();

    // START COPY HERE

    // construct semantics identifier and authentication has, display verification code to the user

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
      throw new RuntimeException("Authentication failed. NB! Use exception handling blocks from above example.". $e);
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

    // END COPY

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
    echo "born " . $authenticationIdentity->getDateOfBirth()->format("D d F o");

    self::assertTrue(true);
  }

}