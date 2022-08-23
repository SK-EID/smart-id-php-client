<?php

namespace Sk\SmartId\Util;

use DateTimeImmutable;
use Sk\SmartId\Api\Data\AuthenticationIdentity;
use Sk\SmartId\Exception\UnprocessableSmartIdResponseException;

class NationalIdentityNumberUtil
{

  /**
   * Detect date-of-birth from national identification number if possible or return null.
   *
   * This method always returns the value for all Estonian and Lithuanian national identification numbers.
   *
   * It also works for older Latvian personal codes but Latvian personal codes issued after July 1st 2017
   * (starting with "32") do not carry date-of-birth.
   *
   * Some (but not all) Smart-ID certificates have date-of-birth on a separate attribute.
   * It is recommended to use that value if present.
   * @see CertificateAttributeUtil#getDateOfBirth(java.security.cert.X509Certificate)
   *
   * @param AuthenticationIdentity $authenticationIdentity
   * @return DateTimeImmutable or null if it cannot be detected from personal code
   */
  public function getDateOfBirth(AuthenticationIdentity $authenticationIdentity): ?\DateTimeImmutable
  {
    $identityNumber = $authenticationIdentity->getIdentityCode();

    switch (strtoupper($authenticationIdentity->getCountry())) {
      case "EE":
      case "LT":
        return $this->parseEeLtDateOfBirth( $authenticationIdentity->getIdentityNumber() );
      case "LV":
        return $this->parseLvDateOfBirth( $authenticationIdentity->getIdentityNumber() );
      default:
        throw new UnprocessableSmartIdResponseException("Unknown country: " . $authenticationIdentity->getCountry());
    }
  }

  public function parseEeLtDateOfBirth(string $eeOrLtNationalIdentityNumber): \DateTimeImmutable
  {
    $birthDay = substr($eeOrLtNationalIdentityNumber, 5, 2);
    $birthMonth = substr($eeOrLtNationalIdentityNumber, 3, 2);
    $birthYearTwoDigit = substr($eeOrLtNationalIdentityNumber, 1, 2);

    switch (substr($eeOrLtNationalIdentityNumber, 0, 1)) {
      case "1":
      case "2":
        $birthYearFourDigit = "18" . $birthYearTwoDigit;
        break;
      case "3":
      case "4":
        $birthYearFourDigit = "19" . $birthYearTwoDigit;
        break;
      case "5":
      case "6":
        $birthYearFourDigit = "20" . $birthYearTwoDigit;
        break;
      default:
        throw new UnprocessableSmartIdResponseException("Invalid personal code " . $eeOrLtNationalIdentityNumber);
    }

    try {
      $dti = new DateTimeImmutable();
      return $dti->setDate($birthYearFourDigit, $birthMonth, $birthDay);
    } catch (\Exception $e) {
      throw new UnprocessableSmartIdResponseException("Could not parse birthdate from nationalIdentityNumber=" . $eeOrLtNationalIdentityNumber, $e);
    }
  }

  public function parseLvDateOfBirth(string $lvNationalIdentityNumber): ?\DateTimeImmutable
  {
    $birthDay = substr($lvNationalIdentityNumber, 0, 2);
    if ("32" == $birthDay) {
      return null;
    }

    $birthMonth = substr($lvNationalIdentityNumber, 2, 2);
    $birthYearTwoDigit = substr($lvNationalIdentityNumber, 4, 2);
    $century = substr($lvNationalIdentityNumber, 7, 1);

    switch ($century) {
      case "0":
        $birthYearFourDigit = "18" . $birthYearTwoDigit;
        break;
      case "1":
        $birthYearFourDigit = "19" . $birthYearTwoDigit;
        break;
      case "2":
        $birthYearFourDigit = "20" . $birthYearTwoDigit;
        break;
      default:
        throw new UnprocessableSmartIdResponseException("Invalid personal code: " . $lvNationalIdentityNumber);
    }

    if (!checkdate ( $birthMonth, $birthDay, $birthYearFourDigit )) {
      throw new UnprocessableSmartIdResponseException("Unable get birthdate from Latvian personal code " . $lvNationalIdentityNumber);
    }
    
    try {
      $dti = new DateTimeImmutable();
      return $dti->setDate($birthYearFourDigit, $birthMonth, $birthDay);
    } catch (\Exception $e) {
      throw new UnprocessableSmartIdResponseException("Unable get birthdate from Latvian personal code " . $lvNationalIdentityNumber . ". Ex:".$e);
    }
  }

}