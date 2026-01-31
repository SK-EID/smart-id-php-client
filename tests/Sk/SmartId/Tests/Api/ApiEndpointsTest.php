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
