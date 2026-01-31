<?php

declare(strict_types=1);

namespace Sk\SmartId\Enum;

enum DeviceLinkType: string
{
    case QR = 'QR';
    case WEB2APP = 'Web2App';
    case APP2APP = 'App2App';
}
