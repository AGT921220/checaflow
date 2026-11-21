<?php

declare(strict_types=1);

namespace App\Features\Attendance\Application;

use App\Features\Attendance\Domain\CompanyClock;
use App\Features\Attendance\Domain\Exceptions\InvalidCompanyTimezone;
use App\Features\Attendance\Domain\Exceptions\InvalidEmployeeProfileId;
use App\Features\Branches\Domain\BranchRepository;
use App\Features\Branches\Domain\Exceptions\BranchDoesNotBelongToCompany;
use App\Features\Branches\Domain\Exceptions\BranchNotFound;
use App\Features\Employees\Domain\EmployeeRepository;
use App\Features\Employees\Domain\Exceptions\EmployeeDoesNotBelongToCompany;
use App\Features\Employees\Domain\Exceptions\EmployeeNotFound;
use App\Features\Schedules\Domain\Exceptions\ScheduleDoesNotBelongToCompany;
use App\Features\Schedules\Domain\Exceptions\ScheduleNotFound;
use App\Features\Schedules\Domain\ScheduleRepository;
use App\Features\Shared\Domain\CompanyId;
use App\Features\Shared\Domain\Exceptions\CompanyNotFound;
use DateTimeZone;
use Exception;

final class LoadEmployeeShift
{
    public function __construct(
        private EmployeeRepository $employees,
        private BranchRepository $branches,
        private ScheduleRepository $schedules,
        private CompanyClock $clock,
    ) {}

    public function handle(CompanyId $companyId, int $employeeProfileId): EmployeeShift
    {
        if ($employeeProfileId < 1) {
            throw new InvalidEmployeeProfileId;
        }

        $employee = $this->employees->find($companyId, $employeeProfileId);

        if ($employee === null) {
            throw new EmployeeNotFound;
        }

        if (! $employee->belongsTo($companyId)) {
            throw new EmployeeDoesNotBelongToCompany;
        }

        $branch = $this->branches->find($companyId, $employee->branchId());

        if ($branch === null) {
            throw new BranchNotFound;
        }

        if (! $branch->belongsTo($companyId)) {
            throw new BranchDoesNotBelongToCompany;
        }

        $schedule = $this->schedules->find($companyId, $employee->scheduleId());

        if ($schedule === null) {
            throw new ScheduleNotFound;
        }

        if (! $schedule->belongsTo($companyId)) {
            throw new ScheduleDoesNotBelongToCompany;
        }

        return new EmployeeShift($employee, $branch, $schedule, $this->zone($companyId));
    }

    private function zone(CompanyId $companyId): DateTimeZone
    {
        $name = $this->clock->timezone($companyId);

        if ($name === null || $name === '') {
            throw new CompanyNotFound;
        }

        try {
            return new DateTimeZone($name);
        } catch (Exception) {
            throw new InvalidCompanyTimezone($name);
        }
    }
}
