<?php

declare(strict_types=1);

namespace Sk\SmartId\Enum;

enum DeviceLinkType: string
{
    case QR = 'qr';
    case WEB2APP = 'web2app';
    case APP2APP = 'app2app';
}
