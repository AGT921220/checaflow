<?php

declare(strict_types=1);

namespace App\Features\Employees\Application;

use App\Features\Branches\Domain\Branch;
use App\Features\Branches\Domain\BranchRepository;
use App\Features\Branches\Domain\Exceptions\BranchDoesNotBelongToCompany;
use App\Features\Branches\Domain\Exceptions\BranchIsInactive;
use App\Features\Branches\Domain\Exceptions\BranchNotFound;
use App\Features\Employees\Domain\CompanyMember;
use App\Features\Employees\Domain\CompanyStaffingLimit;
use App\Features\Employees\Domain\CreateEmployeeData;
use App\Features\Employees\Domain\Employee;
use App\Features\Employees\Domain\EmployeeRepository;
use App\Features\Employees\Domain\Exceptions\EmployeeAlreadyExists;
use App\Features\Employees\Domain\Exceptions\EmployeeCodeAlreadyUsed;
use App\Features\Employees\Domain\Exceptions\EmployeeLimitReached;
use App\Features\Employees\Domain\Exceptions\UserDoesNotBelongToCompany;
use App\Features\Schedules\Domain\Exceptions\ScheduleDoesNotBelongToCompany;
use App\Features\Schedules\Domain\Exceptions\ScheduleIsInactive;
use App\Features\Schedules\Domain\Exceptions\ScheduleNotFound;
use App\Features\Schedules\Domain\Schedule;
use App\Features\Schedules\Domain\ScheduleRepository;
use App\Features\Shared\Domain\CompanyId;
use App\Features\Shared\Domain\Exceptions\CompanyNotFound;
use App\Features\Users\Application\AuthorizeActor;
use App\Features\Users\Domain\CompanyActor;
use App\Features\Users\Domain\PermissionName;

final class CreateEmployee
{
    public function __construct(
        private EmployeeRepository $employees,
        private BranchRepository $branches,
        private ScheduleRepository $schedules,
        private CompanyMember $members,
        private CompanyStaffingLimit $limits,
        private AuthorizeActor $access,
    ) {}

    public function handle(CreateEmployeeData $command): Employee
    {
        $this->access->handle(
            $command->actorRole,
            $command->actorCompanyId,
            PermissionName::EmployeesManage,
            $command->companyId,
        );
        CompanyActor::assertCanOperate($command->actorRole, $command->actorCompanyId, $command->companyId);
        $this->assertCompanyCanHire($command->companyId);
        $this->assertMember($command);
        $this->assertUserHasNoProfile($command->userId);
        $this->assertCodeIsFree($command);
        $branch = $this->branch($command);
        $schedule = $this->schedule($command);

        return $this->employees->save(Employee::hire(
            $command->companyId,
            $command->userId,
            $branch->id(),
            $schedule->id(),
            $command->employeeCode,
            $command->positionTitle,
            $command->hireDate,
            $command->isActive,
        ));
    }

    private function assertCompanyCanHire(CompanyId $companyId): void
    {
        $maximum = $this->limits->maximumEmployees($companyId);

        if ($maximum === null) {
            throw new CompanyNotFound;
        }

        if ($this->employees->count($companyId) >= $maximum) {
            throw new EmployeeLimitReached;
        }
    }

    private function assertMember(CreateEmployeeData $command): void
    {
        if ($this->members->isActiveMember($command->companyId, $command->userId)) {
            return;
        }

        throw new UserDoesNotBelongToCompany;
    }

    private function assertUserHasNoProfile(int $userId): void
    {
        if ($this->employees->userAlreadyHasProfile($userId)) {
            throw new EmployeeAlreadyExists;
        }
    }

    private function assertCodeIsFree(CreateEmployeeData $command): void
    {
        if ($this->employees->codeExists($command->companyId, trim($command->employeeCode))) {
            throw new EmployeeCodeAlreadyUsed;
        }
    }

    private function branch(CreateEmployeeData $command): Branch
    {
        $branch = $this->branches->find($command->companyId, $command->branchId);

        if ($branch === null) {
            throw new BranchNotFound;
        }

        if (! $branch->belongsTo($command->companyId)) {
            throw new BranchDoesNotBelongToCompany;
        }

        if (! $branch->isActive()) {
            throw new BranchIsInactive;
        }

        return $branch;
    }

    private function schedule(CreateEmployeeData $command): Schedule
    {
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

        return $schedule;
    }
}
