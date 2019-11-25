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
namespace Sk\SmartId\Tests\Api;

use Sk\SmartId\Api\Data\SessionEndResultCode;
use Sk\SmartId\Api\Data\SessionResult;
use Sk\SmartId\Api\Data\SessionStatus;
use Sk\SmartId\Api\Data\SessionStatusCode;

class DummyData
{
  const CERTIFICATE = "MIIHHTCCBQWgAwIBAgIQGUbl+snA9KpYlENv0U3w5DANBgkqhkiG9w0BAQsFADBnMQswCQYDVQQGEwJFRTEiMCAGA1UECgwZQVMgU2VydGlmaXRzZWVyaW1pc2tlc2t1czEXMBUGA1UEYQwOTlRSRUUtMTA3NDcwMTMxGzAZBgNVBAMMElRFU1Qgb2YgTlEtU0sgMjAxNjAeFw0xNzAyMDMwODQ2MzlaFw0yMjAyMDIyMTU5NTlaMIG0MQswCQYDVQQGEwJFRTEiMCAGA1UECgwZQVMgU2VydGlmaXRzZWVyaW1pc2tlc2t1czEXMBUGA1UECwwOYXV0aGVudGljYXRpb24xKTAnBgNVBAMMIFNFUktJTixKRVZHRU5JLFBOT0VFLTM4ODA3MjgwMzYzMQ8wDQYDVQQEDAZTRVJLSU4xEDAOBgNVBCoMB0pFVkdFTkkxGjAYBgNVBAUTEVBOT0VFLTM4ODA3MjgwMzYzMIICITANBgkqhkiG9w0BAQEFAAOCAg4AMIICCQKCAgBuw6iuANtxcbXuwbjKIlbP78LjzTEOmH/PWRKcNe4ECn7e236A86ee2XhSOpCUBtpm5xxpcPDZ1bWFZOoERpZVXT9vTID5+DEaGckCyinau8UgYtnb0UxGX+OwzGDBtAuYy+6sp6hlImXXehVRp7+FgxXxB1VlxXtbr/YreCHdc9UdanolRM4l3jW9bbEQNXNB2SjeBEgDubjsFSPFU73wwajrhONi7S6BFLzAkTySCec+9G2fHwoqSkITjzt0ZoKFpd6k2+jCcRKEEiJrSo3+MzC5+QyyrNqvLXBQeF2xyBJvd1Zyt/i4C/z6Tjv74DU/vM6lcHu/gOWPq0aoZwH2IHVVs/+65c1iRgDjqjUE6ignOtVm3rSXXijOcYhFERE83n04ViR95ehiheZTczV0wYDzR6k+l8KvBvnpYRCHR3sHcL9/77DDdbEcfP4mN+EbVYng8oJyVIHlVfQm/ciLBZ1n28oga9ngLwM363lhZ7UoNh/J0ozW/gweKxiqIUq1Ecely6nW/3nibgCqqXXxQPy+TeOiaEFV7lz5ME5ejqYKA8DVxfpgnPlXXjD4Ca867YZ/el8WajJUtTuuA2q0jIHUG7LaGd+/UgMR5mq23RYfhACRmDJTq63rrByrUhvKfN3cKVC/6umipY/ix1h0B99xPE0dD4dnMG03WNHHDQIDAQABo4IBdjCCAXIwCQYDVR0TBAIwADAOBgNVHQ8BAf8EBAMCBLAwVQYDVR0gBE4wTDBABgorBgEEAc4fAxEBMDIwMAYIKwYBBQUHAgEWJGh0dHBzOi8vd3d3LnNrLmVlL2VuL3JlcG9zaXRvcnkvQ1BTLzAIBgYEAI96AQEwHQYDVR0OBBYEFN4MRmH96F+ri+Edcv/Jex04cF1BMB8GA1UdIwQYMBaAFKzDTnTG3849HcTvgWEFtm/dSR09MBMGA1UdJQQMMAoGCCsGAQUFBwMCMHYGCCsGAQUFBwEBBGowaDAjBggrBgEFBQcwAYYXaHR0cDovL2FpYS5zay5lZS9ucTIwMTYwQQYIKwYBBQUHMAKGNWh0dHBzOi8vc2suZWUvdXBsb2FkL2ZpbGVzL1RFU1Rfb2ZfTlEtU0tfMjAxNi5kZXIuY3J0MDEGA1UdEQQqMCikJjAkMSIwIAYDVQQDDBlQTk9FRS0zODgwNzI4MDM2My1QNUNCLU5RMA0GCSqGSIb3DQEBCwUAA4ICAQBGNeT2Opa+PBAZ/RD2qeSL2lLEFURRxX4irh/B0/eqbrpb9+FWRAjRh8lApN+H0uXlgFrDuXb01d9Ja/lLHm+9v2k+HS5pwLEFRjBe2yvorBREJxxygTSD0cAqz4bkMziLCl+FQNsL3UEG9GkDEF/K93p+Vf2ZMRqO16fY8MsfJ5uP0T+27RwKDH6efUwi+WGpsw9gvEujb51me27ldMZewFLadY42hlxlCkWKyJvr8obRVoOSim1AFl6xFE9U2K4e5HK2OmD8LWffFa//ulgIWZQi4aTxMfLJKekcMLHfjpdWzpGFBCWYCeXRb4PhnlbXdgD+4U67ewQwOQu7yAuHfW4I5799eD2aWctknoAc1+412I7p9JxVcLpmt/tjlF9JyY00UnQnjRzW5xByJxVTdPCFRTVvQaD5kJw9cIJ8Cq9RBZzoBx3tSoGUheQiRHAA1xNZAPV2bqaCwNf/d8wGz9ae3QjvYOxD7xj4l1nAfkttSUrliJlgpVpVyU3xGI7Z31Vhp3Whr2WbBtHflCtyncaSSbxOto3qFtec3oeSmeDgmuyX3Rt1rNKUCIU0OI9lTm5N6kBjEqa1jWTIFv/ju+Hgx7G0RIzB5iILFEE1xlw4550wqVCBUvZuQRyaqfNma428b4Z9owmZ9t3kL9bIWCQPQOEfrVXO21ZuxTd/cA==";


  const CERTIFICATE_LEVEL = "ADVANCED";
  const DEMO_HOST_URL = "https://sid.demo.sk.ee/smart-id-rp/v1/";
  const TEST_URL = self::DEMO_HOST_URL;

  const DEMO_RELYING_PARTY_UUID = "00000000-0000-0000-0000-000000000000";
  const DEMO_RELYING_PARTY_NAME = "DEMO";

  const VALID_NATIONAL_IDENTITY = "10101010005";
  const VALID_DOCUMENT_NUMBER = "PNOEE-10101010005-Z1B2-Q";

  const SIGNABLE_TEXT = "hashvalueinbase64";

  const COUNTRY_CODE_EE = "EE";

  /**
   * @return SessionResult
   */
  public static function createSessionEndResult()
  {
    $result = self::createSessionResult( SessionEndResultCode::OK );
    $result->setDocumentNumber( 'PNOEE-31111111111' );
    return $result;
  }

  /**
   * @return SessionStatus
   */
  public static function createUserRefusedSessionStatus()
  {
    $status = self::createCompleteSessionStatus();
    $status->setResult( self::createSessionResult( SessionEndResultCode::USER_REFUSED ) );
    return $status;
  }

  /**
   * @return SessionStatus
   */
  public static function createTimeoutSessionStatus()
  {
    $status = self::createCompleteSessionStatus();
    $status->setResult( self::createSessionResult( SessionEndResultCode::TIMEOUT ) );
    return $status;
  }

  /**
   * @return SessionStatus
   */
  public static function createDocumentUnusableSessionStatus()
  {
    $status = self::createCompleteSessionStatus();
    $status->setResult( self::createSessionResult( SessionEndResultCode::DOCUMENT_UNUSABLE ) );
    return $status;
  }

  /**
   * @param string $endResult
   * @return SessionResult
   */
  public static function createSessionResult( $endResult )
  {
    $result = new SessionResult();
    $result->setEndResult( $endResult );
    return $result;
  }

  /**
   * @return SessionStatus
   */
  private static function createCompleteSessionStatus()
  {
    $status = new SessionStatus();
    $status->setState( SessionStatusCode::COMPLETE );
    return $status;
  }
}
