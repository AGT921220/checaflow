<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Employees;

use App\Features\Branches\Domain\Branch;
use App\Features\Branches\Domain\BranchRepository;
use App\Features\Branches\Domain\Exceptions\BranchDoesNotBelongToCompany;
use App\Features\Branches\Domain\SearchBranchesCriteria;
use App\Features\Employees\Application\AssignEmployeeSchedule;
use App\Features\Employees\Application\CreateEmployee;
use App\Features\Employees\Domain\AssignEmployeeScheduleData;
use App\Features\Employees\Domain\CompanyMember;
use App\Features\Employees\Domain\CompanyStaffingLimit;
use App\Features\Employees\Domain\CreateEmployeeData;
use App\Features\Employees\Domain\Employee;
use App\Features\Employees\Domain\EmployeeRepository;
use App\Features\Employees\Domain\Exceptions\EmployeeIsNotPersisted;
use App\Features\Employees\Domain\Exceptions\EmployeeLimitReached;
use App\Features\Employees\Domain\SearchEmployeesCriteria;
use App\Features\Schedules\Domain\Schedule;
use App\Features\Schedules\Domain\ScheduleRepository;
use App\Features\Schedules\Domain\SearchSchedulesCriteria;
use App\Features\Shared\Domain\CompanyId;
use App\Features\Shared\Domain\Exceptions\InvalidSortColumn;
use App\Features\Users\Application\AuthorizeActor;
use App\Features\Users\Domain\Exceptions\ActorCompanyMismatch;
use App\Features\Users\Domain\Exceptions\PermissionDenied;
use App\Features\Users\Domain\RolePermissions;
use App\Features\Users\Domain\UserRole;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class CreateEmployeeTest extends TestCase
{
    public function test_it_hires_an_employee_inside_the_company(): void
    {
        $companyId = new CompanyId(4);
        $employees = new MemoryEmployees;
        $action = $this->action($employees, $this->branch($companyId), $this->schedule($companyId), 25);

        $employee = $action->handle(new CreateEmployeeData(
            UserRole::CompanyAdmin,
            $companyId,
            $companyId,
            7,
            3,
            8,
            'E-10',
            'Analyst',
            new DateTimeImmutable('2026-01-15'),
        ));

        $this->assertSame('E-10', $employee->employeeCode());
        $this->assertTrue($employee->belongsTo($companyId));
        $this->assertSame(3, $employee->branchId());
        $this->assertSame(8, $employee->scheduleId());
    }

    public function test_it_stops_when_the_plan_is_full(): void
    {
        $companyId = new CompanyId(4);
        $employees = new MemoryEmployees;
        $employees->saved[] = Employee::hire($companyId, 1, 3, 8, 'E-1', null, null)->withId(1);
        $action = $this->action($employees, $this->branch($companyId), $this->schedule($companyId), 1);

        $this->expectException(EmployeeLimitReached::class);

        $action->handle(new CreateEmployeeData(UserRole::CompanyAdmin, $companyId, $companyId, 7, 3, 8, 'E-2', null, null));
    }

    public function test_it_rejects_a_branch_from_another_company(): void
    {
        $companyId = new CompanyId(4);
        $foreign = $this->branch(new CompanyId(9))->withId(3);
        $action = $this->action(new MemoryEmployees, $foreign, $this->schedule($companyId), 10, leakForeignBranch: true);

        $this->expectException(BranchDoesNotBelongToCompany::class);

        $action->handle(new CreateEmployeeData(UserRole::Hr, $companyId, $companyId, 7, 3, 8, 'E-2', null, null));
    }

    public function test_it_rejects_another_company_or_a_super_admin(): void
    {
        $companyId = new CompanyId(4);
        $action = $this->action(new MemoryEmployees, $this->branch($companyId), $this->schedule($companyId), 10);

        $this->expectException(ActorCompanyMismatch::class);

        $action->handle(new CreateEmployeeData(
            UserRole::CompanyAdmin,
            new CompanyId(9),
            $companyId,
            7,
            3,
            8,
            'E-2',
            null,
            null,
        ));
    }

    public function test_a_super_admin_cannot_hire(): void
    {
        $companyId = new CompanyId(4);
        $action = $this->action(new MemoryEmployees, $this->branch($companyId), $this->schedule($companyId), 10);

        $this->expectException(PermissionDenied::class);

        $action->handle(new CreateEmployeeData(
            UserRole::SuperAdmin,
            $companyId,
            $companyId,
            7,
            3,
            8,
            'E-2',
            null,
            null,
        ));
    }

    public function test_assigning_a_schedule_rejects_another_company_or_a_super_admin(): void
    {
        $companyId = new CompanyId(4);
        $action = new AssignEmployeeSchedule(new MemoryEmployees, new MemorySchedules($this->schedule($companyId)), $this->access());

        try {
            $action->handle(new AssignEmployeeScheduleData(UserRole::SuperAdmin, $companyId, $companyId, 1, 8));
            $this->fail('A super admin must not assign a schedule.');
        } catch (PermissionDenied) {
            $this->assertTrue(true);
        }

        $this->expectException(ActorCompanyMismatch::class);

        $action->handle(new AssignEmployeeScheduleData(UserRole::CompanyAdmin, new CompanyId(9), $companyId, 1, 8));
    }

    public function test_employee_search_rejects_unknown_sort_columns(): void
    {
        $this->expectException(InvalidSortColumn::class);

        new SearchEmployeesCriteria(new CompanyId(4), sortColumn: 'email');
    }

    private function action(
        MemoryEmployees $employees,
        Branch $branch,
        Schedule $schedule,
        int $maximum,
        bool $leakForeignBranch = false,
    ): CreateEmployee {
        return new CreateEmployee(
            $employees,
            new MemoryBranches($branch, $leakForeignBranch),
            new MemorySchedules($schedule),
            new MemoryMembers,
            new MemoryLimit($maximum),
            $this->access(),
        );
    }

    private function branch(CompanyId $companyId): Branch
    {
        return Branch::open($companyId, 'Plant', null, null, null, false)->withId(3);
    }

    private function schedule(CompanyId $companyId): Schedule
    {
        return Schedule::open($companyId, 'Office', '09:00', '18:00', 10, 60, ['monday'])->withId(8);
    }

    private function access(): AuthorizeActor
    {
        return new AuthorizeActor(new RolePermissions);
    }
}

final class MemoryEmployees implements EmployeeRepository
{
    /** @var list<Employee> */
    public array $saved = [];

    public function save(Employee $employee): Employee
    {
        try {
            $employee->id();
            $stored = $employee;
        } catch (EmployeeIsNotPersisted) {
            $stored = $employee->withId(count($this->saved) + 1);
        }

        $this->saved[] = $stored;

        return $stored;
    }

    public function find(CompanyId $companyId, int $employeeProfileId): ?Employee
    {
        return null;
    }

    public function search(SearchEmployeesCriteria $criteria): array
    {
        return [];
    }

    
    public function paginate(\App\Features\Employees\Domain\SearchEmployeesCriteria $criteria, \App\Features\Shared\Domain\PageQuery $page): \App\Features\Shared\Domain\PagedResult
    {
        $items = $this->search($criteria);

        return new \App\Features\Shared\Domain\PagedResult($items, count($items), $page->page, $page->perPage);
    }

    public function count(CompanyId $companyId): int
    {
        return count(array_filter(
            $this->saved,
            fn (Employee $employee): bool => $employee->belongsTo($companyId),
        ));
    }

    public function codeExists(CompanyId $companyId, string $employeeCode): bool
    {
        return false;
    }

    public function userAlreadyHasProfile(int $userId): bool
    {
        return false;
    }
}

final class MemoryBranches implements BranchRepository
{
    public function __construct(private Branch $branch, private bool $leakForeignBranch) {}

    public function save(Branch $branch): Branch
    {
        return $branch;
    }

    public function find(CompanyId $companyId, int $branchId): ?Branch
    {
        if (! $this->leakForeignBranch && ! $this->branch->belongsTo($companyId)) {
            return null;
        }

        if ($this->branch->id() !== $branchId) {
            return null;
        }

        return $this->branch;
    }

    public function countActiveByCompanyId(CompanyId $companyId): int
    {
        return 0;
    }

    public function search(SearchBranchesCriteria $criteria): array
    {
        return [];
    }

    public function paginate(\App\Features\Branches\Domain\SearchBranchesCriteria $criteria, \App\Features\Shared\Domain\PageQuery $page): \App\Features\Shared\Domain\PagedResult
    {
        $items = $this->search($criteria);

        return new \App\Features\Shared\Domain\PagedResult($items, count($items), $page->page, $page->perPage);
    }

}

final class MemorySchedules implements ScheduleRepository
{
    public function __construct(private Schedule $schedule) {}

    public function save(Schedule $schedule): Schedule
    {
        return $schedule;
    }

    public function find(CompanyId $companyId, int $scheduleId): ?Schedule
    {
        if (! $this->schedule->belongsTo($companyId) || $this->schedule->id() !== $scheduleId) {
            return null;
        }

        return $this->schedule;
    }

    public function search(SearchSchedulesCriteria $criteria): array
    {
        return [];
    }

    public function paginate(\App\Features\Schedules\Domain\SearchSchedulesCriteria $criteria, \App\Features\Shared\Domain\PageQuery $page): \App\Features\Shared\Domain\PagedResult
    {
        $items = $this->search($criteria);

        return new \App\Features\Shared\Domain\PagedResult($items, count($items), $page->page, $page->perPage);
    }

}

final class MemoryMembers implements CompanyMember
{
    public function isActiveMember(CompanyId $companyId, int $userId): bool
    {
        return $userId === 7;
    }
}

final class MemoryLimit implements CompanyStaffingLimit
{
    public function __construct(private int $maximum) {}

    public function maximumEmployees(CompanyId $companyId): ?int
    {
        return $this->maximum;
    }
}
