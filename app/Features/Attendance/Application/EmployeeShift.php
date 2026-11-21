<?php

declare(strict_types=1);

namespace App\Features\Attendance\Application;

use App\Features\Branches\Domain\Branch;
use App\Features\Employees\Domain\Employee;
use App\Features\Schedules\Domain\Schedule;
use DateTimeZone;

final readonly class EmployeeShift
{
    public function __construct(
        public Employee $employee,
        public Branch $branch,
        public Schedule $schedule,
        public DateTimeZone $timezone,
    ) {}
}
