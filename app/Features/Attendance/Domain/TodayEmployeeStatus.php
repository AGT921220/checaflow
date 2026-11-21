<?php

declare(strict_types=1);

namespace App\Features\Attendance\Domain;

final readonly class TodayEmployeeStatus
{
    /**
     * @param  list<AttendanceLog>  $punches
     */
    public function __construct(
        public int $employeeProfileId,
        public WorkDate $workDate,
        public array $punches,
        public AttendanceStatus $status,
        public ?PunchType $nextPunchType,
        public int $lateMinutes,
        public int $lunchMinutes,
        public int $excessLunchMinutes,
        public int $workedMinutes,
    ) {}
}
