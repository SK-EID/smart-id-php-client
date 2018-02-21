<?php
namespace Sk\SmartId\Api;

use Sk\SmartId\Api\Data\CertificateLevelCode;

class CertificateLevel
{
  /**
   * @var string
   */
  private $certificateLevel;

  /**
   * @var array
   */
  private static $certificateLevels;

  /**
   * @param string $certificateLevel
   */
  public function __construct( $certificateLevel )
  {
    $this->certificateLevel = $certificateLevel;
    self::$certificateLevels = array(
        CertificateLevelCode::ADVANCED  => 1,
        CertificateLevelCode::QUALIFIED => 2,
    );
  }

  /**
   * @param string $certificateLevel
   * @return bool
   */
  public function isEqualOrAbove( $certificateLevel )
  {
    if ( strcasecmp( $this->certificateLevel, $certificateLevel ) === 0 )
    {
      return true;
    }
    elseif ( isset( self::$certificateLevels[ $certificateLevel ], self::$certificateLevels[ $this->certificateLevel ] ) )
    {
      return self::$certificateLevels[ $certificateLevel ] <= self::$certificateLevels[ $this->certificateLevel ];
    }

    return false;
  }
}