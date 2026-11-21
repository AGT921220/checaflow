<?php

declare(strict_types=1);

namespace App\Features\Attendance\Application;

use App\Features\Attendance\Domain\AttendanceDay;
use App\Features\Attendance\Domain\AttendanceLog;
use App\Features\Attendance\Domain\AttendanceLogRepository;
use App\Features\Attendance\Domain\CompanyClock;
use App\Features\Attendance\Domain\Exceptions\ManualPunchRequiresTime;
use App\Features\Attendance\Domain\GeofenceCheck;
use App\Features\Attendance\Domain\PunchSource;
use App\Features\Attendance\Domain\RegisterPunchData;
use App\Features\Attendance\Domain\WorkDate;
use App\Features\Branches\Domain\Exceptions\BranchIsInactive;
use App\Features\Employees\Domain\Employee;
use App\Features\Employees\Domain\Exceptions\EmployeeIsInactive;
use App\Features\Users\Application\AuthorizeActor;
use App\Features\Users\Domain\Exceptions\PersonalAccessDenied;
use App\Features\Users\Domain\PermissionName;
use App\Features\Users\Domain\UserRole;
use DateTimeImmutable;

final class RegisterPunch
{
    public function __construct(
        private LoadEmployeeShift $shift,
        private CompanyClock $clock,
        private AttendanceLogRepository $logs,
        private CalculateDailyAttendance $calculateDailyAttendance,
        private AuthorizeActor $access,
    ) {}

    public function handle(RegisterPunchData $command): AttendanceLog
    {
        $this->access->handle(
            $command->actorRole,
            $command->actorCompanyId,
            PermissionName::AttendancePunch,
            $command->companyId,
            $command->employeeProfileId,
            $command->employeeProfileId,
        );
        $shift = $this->shift->handle($command->companyId, $command->employeeProfileId);
        $this->assertOwnEmployee($command, $shift->employee);
        $this->assertCanPunch($shift);

        $punchedAt = $this->punchedAt($command);
        $workDate = WorkDate::fromInstant($punchedAt, $shift->timezone);
        $day = AttendanceDay::open(
            $command->companyId,
            $shift->employee->id(),
            $workDate,
            $this->logs->forEmployeeOnDate($command->companyId, $shift->employee->id(), $workDate),
        );
        $withinGeofence = (new GeofenceCheck(
            $shift->branch->requiresGeofence(),
            $shift->branch->location(),
            $shift->branch->geofenceRadiusMeters(),
        ))->match($command->location);

        $log = $this->logs->save($day->register(
            $command->punchType,
            $shift->branch->id(),
            $punchedAt,
            $command->location,
            $withinGeofence,
            $command->source,
            $command->ipAddress,
            $command->notes,
        ));

        $this->calculateDailyAttendance->handle($command->companyId, $shift->employee->id(), $workDate);

        return $log;
    }

    private function assertOwnEmployee(RegisterPunchData $command, Employee $employee): void
    {
        if ($command->actorRole !== UserRole::Employee) {
            return;
        }

        if ($employee->id() !== $command->employeeProfileId || ! $employee->belongsTo($command->actorCompanyId)) {
            throw new PersonalAccessDenied;
        }
    }

    private function assertCanPunch(EmployeeShift $shift): void
    {
        if (! $shift->employee->isActive()) {
            throw new EmployeeIsInactive;
        }

        if (! $shift->branch->isActive()) {
            throw new BranchIsInactive;
        }
    }

    private function punchedAt(RegisterPunchData $command): DateTimeImmutable
    {
        if ($command->source !== PunchSource::ManualAdjustment) {
            return $this->clock->now();
        }

        if ($command->punchedAt === null) {
            throw new ManualPunchRequiresTime;
        }

        return $command->punchedAt;
    }
}
