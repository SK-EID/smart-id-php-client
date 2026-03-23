<?php

/*-
 * #%L
 * Smart ID sample PHP client
 * %%
 * Copyright (C) 2018 - 2026 SK ID Solutions AS
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

declare(strict_types=1);

namespace Sk\SmartId\Tests\Session;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sk\SmartId\Session\SessionResult;

class SessionResultTest extends TestCase
{
    #[Test]
    public function fromArrayCreatesResultWithAllFields(): void
    {
        $result = SessionResult::fromArray([
            'endResult' => 'OK',
            'documentNumber' => 'PNOEE-12345678901-MOCK-Q',
            'interactionFlowUsed' => 'verificationCodeChoice',
        ]);

        $this->assertSame('OK', $result->getEndResult());
        $this->assertSame('PNOEE-12345678901-MOCK-Q', $result->getDocumentNumber());
        $this->assertSame('verificationCodeChoice', $result->getInteractionFlowUsed());
        $this->assertTrue($result->isOk());
    }

    #[Test]
    public function fromArrayCreatesResultWithMinimalFields(): void
    {
        $result = SessionResult::fromArray([
            'endResult' => 'USER_REFUSED',
        ]);

        $this->assertSame('USER_REFUSED', $result->getEndResult());
        $this->assertNull($result->getDocumentNumber());
        $this->assertNull($result->getInteractionFlowUsed());
        $this->assertFalse($result->isOk());
    }

    #[Test]
    public function constructorSetsAllProperties(): void
    {
        $result = new SessionResult('OK', 'DOC123', 'displayTextAndPIN');

        $this->assertSame('OK', $result->getEndResult());
        $this->assertSame('DOC123', $result->getDocumentNumber());
        $this->assertSame('displayTextAndPIN', $result->getInteractionFlowUsed());
    }

    #[Test]
    public function isOkReturnsTrueForOkResult(): void
    {
        $result = new SessionResult('OK');
        $this->assertTrue($result->isOk());
    }

    #[Test]
    public function isOkReturnsFalseForNonOkResults(): void
    {
        $this->assertFalse((new SessionResult('USER_REFUSED'))->isOk());
        $this->assertFalse((new SessionResult('TIMEOUT'))->isOk());
        $this->assertFalse((new SessionResult('DOCUMENT_UNUSABLE'))->isOk());
        $this->assertFalse((new SessionResult('WRONG_VC'))->isOk());
    }

    #[Test]
    public function endResultConstantsHaveCorrectValues(): void
    {
        $this->assertSame('OK', SessionResult::END_RESULT_OK);
        $this->assertSame('USER_REFUSED', SessionResult::END_RESULT_USER_REFUSED);
        $this->assertSame('TIMEOUT', SessionResult::END_RESULT_TIMEOUT);
        $this->assertSame('DOCUMENT_UNUSABLE', SessionResult::END_RESULT_DOCUMENT_UNUSABLE);
        $this->assertSame('WRONG_VC', SessionResult::END_RESULT_WRONG_VC);
        $this->assertSame(
            'REQUIRED_INTERACTION_NOT_SUPPORTED_BY_APP',
            SessionResult::END_RESULT_REQUIRED_INTERACTION_NOT_SUPPORTED_BY_APP,
        );
        $this->assertSame('USER_REFUSED_CERT_CHOICE', SessionResult::END_RESULT_USER_REFUSED_CERT_CHOICE);
        $this->assertSame('USER_REFUSED_DISPLAYTEXTANDPIN', SessionResult::END_RESULT_USER_REFUSED_DISPLAYTEXTANDPIN);
        $this->assertSame('USER_REFUSED_VC_CHOICE', SessionResult::END_RESULT_USER_REFUSED_VC_CHOICE);
        $this->assertSame(
            'USER_REFUSED_CONFIRMATIONMESSAGE',
            SessionResult::END_RESULT_USER_REFUSED_CONFIRMATIONMESSAGE,
        );
        $this->assertSame(
            'USER_REFUSED_CONFIRMATIONMESSAGE_WITH_VC_CHOICE',
            SessionResult::END_RESULT_USER_REFUSED_CONFIRMATIONMESSAGE_WITH_VC_CHOICE,
        );
    }

    #[Test]
    public function isUserRefusedReturnsTrueForAllRefusedVariants(): void
    {
        $this->assertTrue((new SessionResult('USER_REFUSED'))->isUserRefused());
        $this->assertTrue((new SessionResult('USER_REFUSED_CERT_CHOICE'))->isUserRefused());
        $this->assertTrue((new SessionResult('USER_REFUSED_DISPLAYTEXTANDPIN'))->isUserRefused());
        $this->assertTrue((new SessionResult('USER_REFUSED_VC_CHOICE'))->isUserRefused());
        $this->assertTrue((new SessionResult('USER_REFUSED_CONFIRMATIONMESSAGE'))->isUserRefused());
        $this->assertTrue((new SessionResult('USER_REFUSED_CONFIRMATIONMESSAGE_WITH_VC_CHOICE'))->isUserRefused());
        $this->assertTrue((new SessionResult('USER_REFUSED_INTERACTION'))->isUserRefused());
    }

    #[Test]
    public function isUserRefusedReturnsFalseForNonRefusedResults(): void
    {
        $this->assertFalse((new SessionResult('OK'))->isUserRefused());
        $this->assertFalse((new SessionResult('TIMEOUT'))->isUserRefused());
    }

    #[Test]
    public function isTimeoutReturnsTrueForTimeout(): void
    {
        $this->assertTrue((new SessionResult('TIMEOUT'))->isTimeout());
        $this->assertFalse((new SessionResult('OK'))->isTimeout());
    }

    #[Test]
    public function isDocumentUnusableReturnsTrueForDocumentUnusable(): void
    {
        $this->assertTrue((new SessionResult('DOCUMENT_UNUSABLE'))->isDocumentUnusable());
        $this->assertFalse((new SessionResult('OK'))->isDocumentUnusable());
    }

    #[Test]
    public function isWrongVCReturnsTrueForWrongVC(): void
    {
        $this->assertTrue((new SessionResult('WRONG_VC'))->isWrongVC());
        $this->assertFalse((new SessionResult('OK'))->isWrongVC());
    }

    #[Test]
    public function isRequiredInteractionNotSupportedReturnsTrueForUnsupported(): void
    {
        $this->assertTrue((new SessionResult('REQUIRED_INTERACTION_NOT_SUPPORTED_BY_APP'))->isRequiredInteractionNotSupported());
        $this->assertFalse((new SessionResult('OK'))->isRequiredInteractionNotSupported());
    }

    #[Test]
    public function isUserRefusedInteractionReturnsTrueForRefusedInteraction(): void
    {
        $this->assertTrue((new SessionResult('USER_REFUSED_INTERACTION'))->isUserRefusedInteraction());
        $this->assertFalse((new SessionResult('OK'))->isUserRefusedInteraction());
    }

    #[Test]
    public function isProtocolFailureReturnsCorrectValues(): void
    {
        $this->assertTrue((new SessionResult('PROTOCOL_FAILURE'))->isProtocolFailure());
        $this->assertFalse((new SessionResult('OK'))->isProtocolFailure());
    }

    #[Test]
    public function isServerErrorReturnsCorrectValues(): void
    {
        $this->assertTrue((new SessionResult('SERVER_ERROR'))->isServerError());
        $this->assertFalse((new SessionResult('OK'))->isServerError());
    }

    #[Test]
    public function isAccountUnusableReturnsCorrectValues(): void
    {
        $this->assertTrue((new SessionResult('ACCOUNT_UNUSABLE'))->isAccountUnusable());
        $this->assertFalse((new SessionResult('OK'))->isAccountUnusable());
    }

    #[Test]
    public function fromArrayWithDetailsSection(): void
    {
        $result = SessionResult::fromArray([
            'endResult' => 'OK',
            'documentNumber' => 'DOC123',
            'details' => [
                'interaction' => 'displayTextAndPIN',
            ],
        ]);

        $this->assertNotNull($result->getDetails());
        $this->assertSame('displayTextAndPIN', $result->getDetails()->getInteraction());
    }

    #[Test]
    public function getDetailsReturnsNullWhenNotPresent(): void
    {
        $result = new SessionResult('OK');
        $this->assertNull($result->getDetails());
    }

    #[Test]
    public function fromArrayThrowsForNonStringEndResult(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('endResult must be a string');

        SessionResult::fromArray(['endResult' => 123]);
    }
}
