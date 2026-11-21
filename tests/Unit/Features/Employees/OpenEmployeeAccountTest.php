<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Employees;

use App\Features\Branches\Domain\Branch;
use App\Features\Branches\Domain\BranchRepository;
use App\Features\Branches\Domain\SearchBranchesCriteria;
use App\Features\Employees\Application\CreateEmployee;
use App\Features\Employees\Application\OpenEmployeeAccount;
use App\Features\Employees\Domain\CompanyMember;
use App\Features\Employees\Domain\CompanyStaffingLimit;
use App\Features\Employees\Domain\Employee;
use App\Features\Employees\Domain\EmployeeAccountRegistrar;
use App\Features\Employees\Domain\EmployeeRepository;
use App\Features\Employees\Domain\OpenEmployeeAccountData;
use App\Features\Employees\Domain\SearchEmployeesCriteria;
use App\Features\Schedules\Domain\Schedule;
use App\Features\Schedules\Domain\ScheduleRepository;
use App\Features\Schedules\Domain\SearchSchedulesCriteria;
use App\Features\Shared\Domain\CompanyId;
use App\Features\Users\Application\AuthorizeActor;
use App\Features\Users\Domain\Exceptions\PermissionDenied;
use App\Features\Users\Domain\RolePermissions;
use App\Features\Users\Domain\UserRole;
use PHPUnit\Framework\TestCase;

final class OpenEmployeeAccountTest extends TestCase
{
    public function test_company_admin_opens_the_account_through_the_registrar(): void
    {
        $companyId = new CompanyId(4);
        $registrar = new MemoryRegistrar;
        $action = new OpenEmployeeAccount($registrar, $this->createEmployee($companyId), $this->access());

        $employee = $action->handle(new OpenEmployeeAccountData(
            UserRole::CompanyAdmin,
            $companyId,
            $companyId,
            'Eva',
            'eva-desk',
            'eva@acme.test',
            '5551112233',
            'plain-secret',
            3,
            8,
            'E-10',
            null,
            null,
        ));

        $this->assertSame(1, $registrar->calls);
        $this->assertSame('eva-desk', $registrar->nickname);
        $this->assertSame('E-10', $employee->employeeCode());
        $this->assertSame(7, $employee->userId());
    }

    public function test_a_super_admin_does_not_open_an_employee_account(): void
    {
        $companyId = new CompanyId(4);
        $registrar = new MemoryRegistrar;
        $action = new OpenEmployeeAccount($registrar, $this->createEmployee($companyId), $this->access());

        try {
            $action->handle(new OpenEmployeeAccountData(
                UserRole::SuperAdmin,
                $companyId,
                $companyId,
                'Eva',
                'eva-desk',
                null,
                null,
                'plain-secret',
                3,
                8,
                'E-10',
                null,
                null,
            ));
            $this->fail('A super admin must not open an employee account.');
        } catch (PermissionDenied) {
            $this->assertSame(0, $registrar->calls);
        }
    }

    private function createEmployee(CompanyId $companyId): CreateEmployee
    {
        return new CreateEmployee(
            new AccountEmployees,
            new AccountBranches(Branch::open($companyId, 'Plant', null, null, null, false)->withId(3)),
            new AccountSchedules(Schedule::open($companyId, 'Office', '09:00', '18:00', 10, 60, ['monday'])->withId(8)),
            new AccountMembers,
            new AccountLimit,
            $this->access(),
        );
    }

    private function access(): AuthorizeActor
    {
        return new AuthorizeActor(new RolePermissions);
    }
}

final class MemoryRegistrar implements EmployeeAccountRegistrar
{
    public int $calls = 0;

    public string $nickname = '';

    public function register(
        CompanyId $companyId,
        string $name,
        string $nickname,
        ?string $email,
        ?string $phone,
        string $password,
    ): int {
        $this->calls++;
        $this->nickname = $nickname;

        return 7;
    }
}

final class AccountEmployees implements EmployeeRepository
{
    public function save(Employee $employee): Employee
    {
        return $employee->withId(1);
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
        return 0;
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

final class AccountBranches implements BranchRepository
{
    public function __construct(private Branch $branch) {}

    public function save(Branch $branch): Branch
    {
        return $branch;
    }

    public function find(CompanyId $companyId, int $branchId): ?Branch
    {
        if (! $this->branch->belongsTo($companyId) || $this->branch->id() !== $branchId) {
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

final class AccountSchedules implements ScheduleRepository
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

final class AccountMembers implements CompanyMember
{
    public function isActiveMember(CompanyId $companyId, int $userId): bool
    {
        return $userId === 7;
    }
}

final class AccountLimit implements CompanyStaffingLimit
{
    public function maximumEmployees(CompanyId $companyId): ?int
    {
        return 10;
    }
}
