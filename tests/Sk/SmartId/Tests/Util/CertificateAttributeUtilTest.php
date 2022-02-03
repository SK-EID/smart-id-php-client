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

namespace Sk\SmartId\Tests\Api\Util;

use PHPUnit\Framework\TestCase;
use Sk\SmartId\Tests\Api\DummyData;
use Sk\SmartId\Util\CertificateAttributeUtil;

class CertificateAttributeUtilTest extends TestCase {

  /**
   * @test
   */
  public function testGetDateOfBirthCertificateAttribute_attributeIsPresent_valueReturned() {

    $certWithDob = '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';

    $dobUtil = new CertificateAttributeUtil();
    $dob = $dobUtil->getDateOfBirthCertificateAttribute($certWithDob);

    $this->assertEquals('1903-03-03', $dob->format('Y-m-d') );
  }

  /**
   * @test
   */
  public function testGetDateOfBirthCertificateAttribute_dateOfBirthAttributeIsNotPresent_nullReturned() {
    $certWithoutDob = DummyData::CERTIFICATE_WITHOUT_DOB_FIELD;

    $dobUtil = new CertificateAttributeUtil();
    $dob = $dobUtil->getDateOfBirthCertificateAttribute($certWithoutDob);

    $this->assertNull( $dob );
  }

}