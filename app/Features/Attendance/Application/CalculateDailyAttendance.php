<?php

declare(strict_types=1);

namespace App\Features\Attendance\Application;

use App\Features\Attendance\Domain\AttendanceLogRepository;
use App\Features\Attendance\Domain\DailyAttendance;
use App\Features\Attendance\Domain\DailyAttendanceRepository;
use App\Features\Attendance\Domain\WorkDate;
use App\Features\Shared\Domain\CompanyId;

final class CalculateDailyAttendance
{
    public function __construct(
        private LoadEmployeeShift $shift,
        private AttendanceLogRepository $logs,
        private DailyAttendanceRepository $summaries,
    ) {}

    public function handle(CompanyId $companyId, int $employeeProfileId, WorkDate $workDate): DailyAttendance
    {
        $shift = $this->shift->handle($companyId, $employeeProfileId);
        $punches = $this->logs->forEmployeeOnDate($companyId, $shift->employee->id(), $workDate);
        $existing = $this->summaries->find($companyId, $shift->employee->id(), $workDate);

        return $this->summaries->save(DailyAttendance::calculate(
            $companyId,
            $shift->employee->id(),
            $shift->employee->branchId(),
            $shift->schedule->id(),
            $workDate,
            $punches,
            $shift->schedule->clockIn()->hour(),
            $shift->schedule->clockIn()->minute(),
            $shift->schedule->lateToleranceMinutes(),
            $shift->schedule->allowedLunchMinutes(),
            $shift->timezone,
            $existing?->id(),
        ));
    }
}
