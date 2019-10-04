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

use PHPUnit\Framework\TestCase;
use Sk\SmartId\Api\Data\HashType;
use Sk\SmartId\Api\Data\SignableData;

class SignableDataTest extends TestCase
{
  const DATA_TO_SIGN = 'Hello World!';
  const SHA512_HASH_IN_BASE64 = 'hhhE1nBOhXP+w02WfiC8/vPUJM9IvgTm3AjyvVjHKXQzcQFerYkcw88cnTS0kmS1EHUbH/nlN5N7xGtdb/TsyA==';
  const SHA384_HASH_IN_BASE64 = 'v9dsDrvQBv7lg0EFR8GIewKSvnbVgtlsJC0qeScj4/1v0GH51c/RO4+WE1jmrbpK';
  const SHA256_HASH_IN_BASE64 = 'f4OxZX/x/FO5LcGBSKHWXfwtSx+j1ncoSt3SABJtkGk=';

  /**
   * @test
   */
  public function signableData_withDefaultHashType_sha512()
  {
    $signableData = new SignableData( self::DATA_TO_SIGN );
    $this->assertEquals( HashType::SHA512, $signableData->getHashType() );
    $this->assertEquals( self::SHA512_HASH_IN_BASE64, $signableData->calculateHashInBase64() );
    $this->assertEquals( base64_decode( self::SHA512_HASH_IN_BASE64 ), $signableData->calculateHash() );
  }

  /**
   * @test
   */
  public function signableData_with_sha256()
  {
    $signableData = new SignableData( self::DATA_TO_SIGN );
    $signableData->setHashType( HashType::SHA256 );
    $this->assertEquals( HashType::SHA256, $signableData->getHashType() );
    $this->assertEquals( self::SHA256_HASH_IN_BASE64, $signableData->calculateHashInBase64() );
    $this->assertEquals( base64_decode( self::SHA256_HASH_IN_BASE64 ), $signableData->calculateHash() );
  }

  /**
   * @test
   */
  public function signableData_with_sha384()
  {
    $signableData = new SignableData( self::DATA_TO_SIGN );
    $signableData->setHashType( HashType::SHA384 );
    $this->assertEquals( HashType::SHA384, $signableData->getHashType() );
    $this->assertEquals( self::SHA384_HASH_IN_BASE64, $signableData->calculateHashInBase64() );
    $this->assertEquals( base64_decode( self::SHA384_HASH_IN_BASE64 ), $signableData->calculateHash() );
  }
}
