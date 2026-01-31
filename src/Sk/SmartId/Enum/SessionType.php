<?php

declare(strict_types=1);

namespace Sk\SmartId\Enum;

enum SessionType: string
{
    case AUTHENTICATION = 'auth';
    case SIGNATURE = 'sign';
    case CERTIFICATE = 'cert';
}
