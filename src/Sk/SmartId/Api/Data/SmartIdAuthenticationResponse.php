<?php
/*-
 * #%L
 * Smart ID sample PHP client
 * %%
 * Copyright (C) 2018 - 2019 SK ID Solutions AS
 * %%
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 * #L%
 */
namespace Sk\SmartId\Api\Data;

use Sk\SmartId\Exception\TechnicalErrorException;

class SmartIdAuthenticationResponse extends PropertyMapper
{
  /**
   * @var string
   */
  private $endResult;

  /**
   * @var string
   */
  private $signedData;

  /**
   * @var string
   */
  private $valueInBase64;

  /**
   * @var string
   */
  private $algorithmName;

  /**
   * @var string
   */
  private $certificate;

  /**
   * @var string
   */
  private $requestedCertificateLevel;

  /**
   * @var string
   */
  private $certificateLevel;

  /**
   * @var string
   */
  private $state;

  /**
   * @var array
   */
  private $ignoredProperties;

  /**
   * @var string
   */
  private $interactionFlowUsed;

  /**
   * @var string
   */
  private $documentNumber;

  /**
   * @return string
   */
  public function getEndResult(): ?string
  {
    return $this->endResult;
  }

  /**
   * @param string $endResult
   * @return $this
   */
  public function setEndResult(string $endResult ): SmartIdAuthenticationResponse
  {
    $this->endResult = $endResult;
    return $this;
  }

  /**
   * @return string
   */
  public function getSignedData(): string
  {
    return $this->signedData;
  }

  /**
   * @param string $signedData
   * @return $this
   */
  public function setSignedData(string $signedData ): SmartIdAuthenticationResponse
  {
    $this->signedData = $signedData;
    return $this;
  }

  /**
   * @return string
   */
  public function getValueInBase64(): string
  {
    return $this->valueInBase64;
  }

  /**
   * @param string|null $valueInBase64
   * @return $this
   */
  public function setValueInBase64(?string $valueInBase64 ): SmartIdAuthenticationResponse
  {
    $this->valueInBase64 = $valueInBase64;
    return $this;
  }

  /**
   * @return string
   */
  public function getAlgorithmName(): string
  {
    return $this->algorithmName;
  }

  /**
   * @param string|null $algorithmName
   * @return $this
   */
  public function setAlgorithmName(?string $algorithmName ): SmartIdAuthenticationResponse
  {
    $this->algorithmName = $algorithmName;
    return $this;
  }

  /**
   * @return string
   */
  public function getCertificate(): ?string
  {
    return $this->certificate;
  }

  /**
   * @return array
   */
  public function getParsedCertificate(): array
  {
    return CertificateParser::parseX509Certificate( $this->certificate );
  }

  /**
   * @return AuthenticationCertificate
   */
  public function getCertificateInstance(): AuthenticationCertificate
  {
    $parsed = CertificateParser::parseX509Certificate( $this->certificate );
    return new AuthenticationCertificate( $parsed );
  }

  /**
   * @param string|null $certificate
   * @return $this
   */
  public function setCertificate(?string $certificate ): SmartIdAuthenticationResponse
  {
    $this->certificate = $certificate;
    return $this;
  }

  /**
   * @return string
   */
  public function getCertificateLevel(): string
  {
    return $this->certificateLevel;
  }

  /**
   * @param string|null $certificateLevel
   * @return $this
   */
  public function setCertificateLevel(?string $certificateLevel ): SmartIdAuthenticationResponse
  {
    $this->certificateLevel = $certificateLevel;
    return $this;
  }

  /**
   * @return string
   */
  public function getRequestedCertificateLevel(): ?string
  {
    return $this->requestedCertificateLevel;
  }

  /**
   * @param string|null $requestedCertificateLevel
   * @return $this
   */
  public function setRequestedCertificateLevel(?string $requestedCertificateLevel ): SmartIdAuthenticationResponse
  {
    $this->requestedCertificateLevel = $requestedCertificateLevel;
    return $this;
  }

  /**
   * @throws TechnicalErrorException
   * @return string
   */
  public function getValue(): string
  {
    if ( base64_decode( $this->valueInBase64, true ) === false )
    {
      throw new TechnicalErrorException( 'Failed to parse signature value in base64. Probably incorrectly 
                encoded base64 string: ' . $this->valueInBase64 );
    }
    return base64_decode( $this->valueInBase64, true );
  }

  /**
   * @param string $state
   * @return $this
   */
  public function setState(string $state ): SmartIdAuthenticationResponse
  {
    $this->state = $state;
    return $this;
  }

  /**
   * @return string
   */
  public function getState(): string
  {
    return $this->state;
  }

    /**
     * @param array|null $ignoredProperties
     * @return SmartIdAuthenticationResponse
     */
  public function setIgnoredProperties(?array $ignoredProperties): SmartIdAuthenticationResponse
  {
      $this->ignoredProperties = $ignoredProperties;
      return $this;
  }

  /**
   * @return string
   */
  public function getInteractionFlowUsed(): string
  {
      return $this->interactionFlowUsed;
  }

  /**
   * @param string|null $interactionFlowUsed
   * @return SmartIdAuthenticationResponse
   */
  public function setInteractionFlowUsed(?string $interactionFlowUsed): SmartIdAuthenticationResponse
  {
      $this->interactionFlowUsed = $interactionFlowUsed;
      return $this;
  }

  /**
   * @return bool
   */
  public function isRunningState(): bool
  {
    return strcasecmp( SessionStatusCode::RUNNING, $this->state ) == 0;
  }

  public function setDocumentNumber(?string $documentNumber): SmartIdAuthenticationResponse
  {
    $this->documentNumber = $documentNumber;
    return $this;
  }

  /**
   * @return string
   */
  public function getDocumentNumber(): ?string
  {
    return $this->documentNumber;
  }

}
