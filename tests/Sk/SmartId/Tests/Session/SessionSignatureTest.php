<?php

declare(strict_types=1);

namespace Sk\SmartId\Tests\Session;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sk\SmartId\Exception\ValidationException;
use Sk\SmartId\Session\SessionSignature;

class SessionSignatureTest extends TestCase
{
    #[Test]
    public function constructorSetsProperties(): void
    {
        $sig = new SessionSignature('signatureValue', 'SHA512WithRSA');

        $this->assertSame('signatureValue', $sig->getValue());
        $this->assertSame('SHA512WithRSA', $sig->getAlgorithm());
    }

    #[Test]
    public function fromArrayCreatesSignature(): void
    {
        $data = [
            'value' => 'base64SignatureValue',
            'algorithm' => 'SHA256WithRSA',
        ];

        $sig = SessionSignature::fromArray($data);

        $this->assertSame('base64SignatureValue', $sig->getValue());
        $this->assertSame('SHA256WithRSA', $sig->getAlgorithm());
    }

    #[Test]
    public function getDecodedValueReturnsDecodedBase64(): void
    {
        $originalValue = 'test signature data';
        $base64Value = base64_encode($originalValue);
        $sig = new SessionSignature($base64Value, 'SHA512WithRSA');

        $decoded = $sig->getDecodedValue();

        $this->assertSame($originalValue, $decoded);
    }

    #[Test]
    public function getDecodedValueThrowsOnInvalidBase64(): void
    {
        $sig = new SessionSignature('not-valid-base64!!!', 'SHA512WithRSA');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid base64 encoded signature value');

        $sig->getDecodedValue();
    }
}
