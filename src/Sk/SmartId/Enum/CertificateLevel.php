<?php

declare(strict_types=1);

namespace Sk\SmartId\Enum;

enum CertificateLevel: string
{
    case QUALIFIED = 'QUALIFIED';
    case ADVANCED = 'ADVANCED';
}
