<?php
namespace Sk\SmartId\Api\Data;

class SessionResult extends PropertyMapper
{
  /**
   * @var string
   */
  private $endResult;

  /**
   * @var string
   */
  private $documentNumber;

  /**
   * @return string
   */
  public function getEndResult()
  {
    return $this->endResult;
  }

  /**
   * @param string $endResult
   * @return $this
   */
  public function setEndResult( $endResult )
  {
    $this->endResult = $endResult;
    return $this;
  }

  /**
   * @return string
   */
  public function getDocumentNumber()
  {
    return $this->documentNumber;
  }

  /**
   * @param string $documentNumber
   * @return $this
   */
  public function setDocumentNumber( $documentNumber )
  {
    $this->documentNumber = $documentNumber;
    return $this;
  }
}