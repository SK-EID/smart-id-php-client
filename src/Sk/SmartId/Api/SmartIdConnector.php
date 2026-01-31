<?php

declare(strict_types=1);

namespace Sk\SmartId\Api;

use Sk\SmartId\DeviceLink\DeviceLinkAuthenticationRequest;
use Sk\SmartId\DeviceLink\DeviceLinkAuthenticationResponse;
use Sk\SmartId\Notification\NotificationAuthenticationRequest;
use Sk\SmartId\Notification\NotificationAuthenticationResponse;
use Sk\SmartId\Session\SessionStatus;

interface SmartIdConnector
{
    public function initiateDeviceLinkAuthentication(
        DeviceLinkAuthenticationRequest $request,
    ): DeviceLinkAuthenticationResponse;

    public function initiateNotificationAuthentication(
        NotificationAuthenticationRequest $request,
        ?string $documentNumber = null,
        ?string $semanticsIdentifier = null,
    ): NotificationAuthenticationResponse;

    public function getSessionStatus(string $sessionId, ?int $timeoutMs = null): SessionStatus;
}
