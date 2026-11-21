<?php

declare(strict_types=1);

namespace App\Features\Employees\Application;

use App\Features\Employees\Domain\AssignEmployeeScheduleData;
use App\Features\Employees\Domain\Employee;
use App\Features\Employees\Domain\EmployeeRepository;
use App\Features\Employees\Domain\Exceptions\EmployeeDoesNotBelongToCompany;
use App\Features\Employees\Domain\Exceptions\EmployeeNotFound;
use App\Features\Schedules\Domain\Exceptions\ScheduleDoesNotBelongToCompany;
use App\Features\Schedules\Domain\Exceptions\ScheduleIsInactive;
use App\Features\Schedules\Domain\Exceptions\ScheduleNotFound;
use App\Features\Schedules\Domain\ScheduleRepository;
use App\Features\Users\Application\AuthorizeActor;
use App\Features\Users\Domain\CompanyActor;
use App\Features\Users\Domain\PermissionName;

final class AssignEmployeeSchedule
{
    public function __construct(
        private EmployeeRepository $employees,
        private ScheduleRepository $schedules,
        private AuthorizeActor $access,
    ) {}

    public function handle(AssignEmployeeScheduleData $command): Employee
    {
        $this->access->handle(
            $command->actorRole,
            $command->actorCompanyId,
            PermissionName::EmployeesManage,
            $command->companyId,
        );
        CompanyActor::assertCanOperate($command->actorRole, $command->actorCompanyId, $command->companyId);

        $employee = $this->employees->find($command->companyId, $command->employeeProfileId);

        if ($employee === null) {
            throw new EmployeeNotFound;
        }

        if (! $employee->belongsTo($command->companyId)) {
            throw new EmployeeDoesNotBelongToCompany;
        }

        $schedule = $this->schedules->find($command->companyId, $command->scheduleId);

        if ($schedule === null) {
            throw new ScheduleNotFound;
        }

        if (! $schedule->belongsTo($command->companyId)) {
            throw new ScheduleDoesNotBelongToCompany;
        }

        if (! $schedule->isActive()) {
            throw new ScheduleIsInactive;
        }

        if ($employee->scheduleId() === $schedule->id()) {
            return $employee;
        }

        return $this->employees->save($employee->assignSchedule($schedule->id()));
    }
}
