<?php
namespace Sk\SmartId\Api\Data;

class SmartIdAuthenticationResultError
{
  const INVALID_END_RESULT = 'Response end result verification failed.';
  const SIGNATURE_VERIFICATION_FAILURE = 'Signature verification failed.';
  const CERTIFICATE_EXPIRED = 'Signer\'s certificate expired.';
  const CERTIFICATE_NOT_TRUSTED = 'Signer\'s certificate is not trusted.';
  const CERTIFICATE_LEVEL_MISMATCH = 'Signer\'s certificate level does not match with the requested level.';
}