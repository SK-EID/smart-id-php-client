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
use Sk\SmartId\Api\Data\AuthenticationHash;
use Sk\SmartId\Api\Data\HashType;

class AuthenticationHashTest extends TestCase
{
  /**
   * @test
   */
  public function generated_signableData_withDefaultHashType_sha512()
  {
    $authenticationHash = AuthenticationHash::generateRandomHash( HashType::SHA512 );
    $this->assertEquals( HashType::SHA512, $authenticationHash->getHashType() );
    $this->assertEquals( base64_decode( $authenticationHash->calculateHashInBase64() ),
        $authenticationHash->getHash() );
  }

  /**
   * @test
   */
  public function generated_signableData_withDefaultHashType_sha384()
  {
    $authenticationHash = AuthenticationHash::generateRandomHash( HashType::SHA384 );
    $this->assertEquals( HashType::SHA384, $authenticationHash->getHashType() );
    $this->assertEquals( base64_decode( $authenticationHash->calculateHashInBase64() ),
        $authenticationHash->getHash() );
  }

  /**
   * @test
   */
  public function generated_signableData_withDefaultHashType_sha256()
  {
    $authenticationHash = AuthenticationHash::generateRandomHash( HashType::SHA256 );
    $this->assertEquals( HashType::SHA256, $authenticationHash->getHashType() );
    $this->assertEquals( base64_decode( $authenticationHash->calculateHashInBase64() ),
        $authenticationHash->getHash() );
  }
}
