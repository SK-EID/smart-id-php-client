<?php
namespace Sk\SmartId\Api\Data;

class CertificateParser
{
  const BEGIN_CERT = '-----BEGIN CERTIFICATE-----';
  const END_CERT = '-----END CERTIFICATE-----';

  /**
   * @param string $certificateValue
   * @return array
   */
  public static function parseX509Certificate( $certificateValue )
  {
    $certificateString = self::BEGIN_CERT . "\n" . $certificateValue . "\n" . self::END_CERT;
    return openssl_x509_parse( $certificateString );
  }
}