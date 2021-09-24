<?php

namespace Sk\SmartId\Util;

use DateTimeImmutable;
use phpseclib3\File\X509;

class CertificateAttributeUtil
{

  /**
   * Get Date-of-birth (DoB) from a specific certificate header (if present).
   *
   * NB! This attribute may be present on some newer certificates (since ~ May 2021) but not all.
   *
   * @see NationalIdentityNumberUtil#getDateOfBirth(AuthenticationIdentity) for fallback.
   *
   * @param string $x509Certificate Certificate to read the date-of-birth attribute from
   * @return DateTimeImmutable date-of-birth or null if this attribute is not set.
   */
  public function getDateOfBirthCertificateAttribute(string $x509Certificate): ?DateTimeImmutable
  {
    $dobAsString = $this->getDateOfBirthFromCertificateField($x509Certificate);

    if ($dobAsString == null) {
      return null;
    }

    $timestamp = strtotime($dobAsString);

    $dti = new DateTimeImmutable();
    return $dti->setTimestamp($timestamp);
  }

  public function getDateOfBirthFromCertificateField(string $certAsString)
  {

    $x509 = new X509();
    $csr = $x509->loadX509($certAsString);

    $arrayIterator = new \RecursiveArrayIterator($csr);
    $recursiveIterator = new \RecursiveIteratorIterator($arrayIterator, \RecursiveIteratorIterator::SELF_FIRST);

    foreach ($recursiveIterator as $key => $value) {
      if (is_array($value) && array_key_exists('type', $value) && $value['type'] == '1.3.6.1.5.5.7.9.1') {
        if (is_array($value['value'][0]) && array_key_exists('generalTime', $value['value'][0])) {
          return $value['value'][0]['generalTime'];
        }
      }
    }
    return null;
  }

}