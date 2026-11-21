<?php

declare(strict_types=1);

namespace App\Features\Attendance\Domain;

enum PunchSource: string
{
    case MobileWeb = 'mobile_web';
    case MobileApp = 'mobile_app';
    case WebAdmin = 'web_admin';
    case ManualAdjustment = 'manual_adjustment';
}
