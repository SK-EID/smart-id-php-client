<?php

declare(strict_types=1);

namespace Sk\SmartId\Api;

class ApiEndpoints
{
    public const DEVICE_LINK_ANONYMOUS_AUTHENTICATION = '/authentication/device-link/anonymous';

    public const NOTIFICATION_AUTHENTICATION_BY_DOCUMENT_NUMBER = '/authentication/document/%s';

    public const NOTIFICATION_AUTHENTICATION_BY_SEMANTICS_IDENTIFIER = '/authentication/etsi/%s';

    public const SESSION_STATUS = '/session/%s';
}
