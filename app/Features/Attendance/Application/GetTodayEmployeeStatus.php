<?php

declare(strict_types=1);

namespace App\Features\Attendance\Application;

use App\Features\Attendance\Domain\AttendanceDay;
use App\Features\Attendance\Domain\AttendanceLogRepository;
use App\Features\Attendance\Domain\CompanyClock;
use App\Features\Attendance\Domain\DailyAttendance;
use App\Features\Attendance\Domain\TodayEmployeeStatus;
use App\Features\Attendance\Domain\WorkDate;
use App\Features\Employees\Domain\Exceptions\EmployeeIsInactive;
use App\Features\Shared\Domain\CompanyId;

final class GetTodayEmployeeStatus
{
    public function __construct(
        private LoadEmployeeShift $shift,
        private CompanyClock $clock,
        private AttendanceLogRepository $logs,
    ) {}

    public function handle(CompanyId $companyId, int $employeeProfileId): TodayEmployeeStatus
    {
        $shift = $this->shift->handle($companyId, $employeeProfileId);

        if (! $shift->employee->isActive()) {
            throw new EmployeeIsInactive;
        }

        $workDate = WorkDate::fromInstant($this->clock->now(), $shift->timezone);
        $punches = $this->logs->forEmployeeOnDate($companyId, $shift->employee->id(), $workDate);
        $day = AttendanceDay::open($companyId, $shift->employee->id(), $workDate, $punches);
        $summary = DailyAttendance::calculate(
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
        );

        return new TodayEmployeeStatus(
            $shift->employee->id(),
            $workDate,
            $day->punches(),
            $summary->status(),
            $day->nextAllowed(),
            $summary->lateMinutes(),
            $summary->lunchMinutes(),
            $summary->excessLunchMinutes(),
            $summary->workedMinutes(),
        );
    }
}
