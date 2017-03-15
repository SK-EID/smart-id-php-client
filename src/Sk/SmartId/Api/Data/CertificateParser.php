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
    $certificateString = self::getPemCertificate( $certificateValue );
    return openssl_x509_parse( $certificateString );
  }

  /**
   * @param string $certificateValue
   * @return string
   */
  public static function getPemCertificate( $certificateValue )
  {
    if ( substr( $certificateValue, 0, strlen( self::BEGIN_CERT ) ) === self::BEGIN_CERT )
    {
      $certificateValue = substr( $certificateValue, strlen( self::BEGIN_CERT ) );
    }

    if ( substr( $certificateValue, -strlen( self::END_CERT ) ) === self::END_CERT )
    {
      $certificateValue = substr( $certificateValue, 0, -strlen( self::END_CERT ) );
    }

    $certificateValue = implode( "\n", str_split( $certificateValue, 64 ) );
    return self::BEGIN_CERT . "\n" . $certificateValue . "\n" . self::END_CERT;
  }
}