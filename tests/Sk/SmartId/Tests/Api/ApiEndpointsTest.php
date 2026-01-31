<?php

declare(strict_types=1);

namespace Sk\SmartId\Tests\Api;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sk\SmartId\Api\ApiEndpoints;

class ApiEndpointsTest extends TestCase
{
    #[Test]
    public function deviceLinkAnonymousAuthenticationHasCorrectValue(): void
    {
        $this->assertSame(
            '/authentication/device-link/anonymous',
            ApiEndpoints::DEVICE_LINK_ANONYMOUS_AUTHENTICATION,
        );
    }

    #[Test]
    public function notificationAuthenticationByDocumentNumberHasCorrectValue(): void
    {
        $this->assertSame(
            '/authentication/document/%s',
            ApiEndpoints::NOTIFICATION_AUTHENTICATION_BY_DOCUMENT_NUMBER,
        );
    }

    #[Test]
    public function notificationAuthenticationBySemanticsIdentifierHasCorrectValue(): void
    {
        $this->assertSame(
            '/authentication/etsi/%s',
            ApiEndpoints::NOTIFICATION_AUTHENTICATION_BY_SEMANTICS_IDENTIFIER,
        );
    }

    #[Test]
    public function sessionStatusHasCorrectValue(): void
    {
        $this->assertSame('/session/%s', ApiEndpoints::SESSION_STATUS);
    }
}
