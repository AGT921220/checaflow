<?php

declare(strict_types=1);

namespace App\Features\Attendance\Domain;

enum AttendanceStatus: string
{
    case OnTime = 'on_time';
    case Late = 'late';
    case Incomplete = 'incomplete';
    case Absent = 'absent';
}
