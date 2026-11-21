<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Employees;

use App\Features\Branches\Domain\Branch;
use App\Features\Branches\Domain\BranchRepository;
use App\Features\Branches\Domain\SearchBranchesCriteria;
use App\Features\Employees\Application\UpdateEmployee;
use App\Features\Employees\Domain\Employee;
use App\Features\Employees\Domain\EmployeeRepository;
use App\Features\Employees\Domain\SearchEmployeesCriteria;
use App\Features\Employees\Domain\UpdateEmployeeData;
use App\Features\Schedules\Domain\Schedule;
use App\Features\Schedules\Domain\ScheduleRepository;
use App\Features\Schedules\Domain\SearchSchedulesCriteria;
use App\Features\Shared\Domain\CompanyId;
use App\Features\Users\Application\AuthorizeActor;
use App\Features\Users\Domain\PlatformUser;
use App\Features\Users\Domain\ProfilePhotoStore;
use App\Features\Users\Domain\RolePermissions;
use App\Features\Users\Domain\UserRepository;
use App\Features\Users\Domain\UserRole;
use PHPUnit\Framework\TestCase;

final class UpdateEmployeeTest extends TestCase
{
    public function test_hr_updates_employee_contact_and_assignment(): void
    {
        $companyId = new CompanyId(4);
        $employees = new UpdatingEmployees;
        $employees->saved[] = Employee::hire($companyId, 7, 3, 8, 'E-10', 'Old', null)->withId(1);
        $users = new UpdatingUsers;
        $users->saved[7] = PlatformUser::register(
            $companyId,
            'Eva',
            'Old',
            'eva-old',
            'eva@acme.test',
            null,
            UserRole::Employee,
            true,
            null,
        )->withId(7);

        $action = new UpdateEmployee(
            $employees,
            $users,
            new UpdatingBranches(Branch::open($companyId, 'Plant', null, null, null, false)->withId(3)),
            new UpdatingSchedules(Schedule::open($companyId, 'Office', '09:00', '18:00', 10, 60, ['monday'])->withId(8)),
            new MemoryPhotos,
            new AuthorizeActor(new RolePermissions),
        );

        $employee = $action->handle(new UpdateEmployeeData(
            UserRole::Hr,
            $companyId,
            $companyId,
            1,
            'Eva New',
            'eva-new',
            'eva.new@acme.test',
            '555',
            3,
            8,
            'E-11',
            'Lead',
            null,
            true,
        ));

        $this->assertSame('E-11', $employee->employeeCode());
        $this->assertSame('Lead', $employee->positionTitle());
        $this->assertSame('eva-new', $users->saved[7]->nickname());
        $this->assertSame('eva.new@acme.test', $users->saved[7]->email());
    }
}

final class UpdatingEmployees implements EmployeeRepository
{
    /** @var list<Employee> */
    public array $saved = [];

    public function save(Employee $employee): Employee
    {
        foreach ($this->saved as $index => $row) {
            if ($row->id() === $employee->id()) {
                $this->saved[$index] = $employee;

                return $employee;
            }
        }

        $this->saved[] = $employee;

        return $employee;
    }

    public function find(CompanyId $companyId, int $employeeProfileId): ?Employee
    {
        foreach ($this->saved as $employee) {
            if ($employee->belongsTo($companyId) && $employee->id() === $employeeProfileId) {
                return $employee;
            }
        }

        return null;
    }

    public function search(SearchEmployeesCriteria $criteria): \App\Features\Shared\Domain\PagedResult
    {
        return new \App\Features\Shared\Domain\PagedResult([], 0, $criteria->page, $criteria->perPage);
    }

    

    public function count(CompanyId $companyId): int
    {
        return count($this->saved);
    }

    public function codeExists(CompanyId $companyId, string $employeeCode): bool
    {
        foreach ($this->saved as $employee) {
            if ($employee->belongsTo($companyId) && $employee->employeeCode() === $employeeCode) {
                return true;
            }
        }

        return false;
    }

    public function userAlreadyHasProfile(int $userId): bool
    {
        return false;
    }
}

final class UpdatingUsers implements UserRepository
{
    /** @var array<int, PlatformUser> */
    public array $saved = [];

    public function findByEmail(string $email): ?PlatformUser
    {
        foreach ($this->saved as $user) {
            if ($user->email() === $email) {
                return $user;
            }
        }

        return null;
    }

    public function findByNickname(string $nickname): ?PlatformUser
    {
        foreach ($this->saved as $user) {
            if ($user->nickname() === $nickname) {
                return $user;
            }
        }

        return null;
    }

    public function find(int $userId): ?PlatformUser
    {
        return $this->saved[$userId] ?? null;
    }

    public function save(PlatformUser $user): PlatformUser
    {
        $this->saved[$user->id()] = $user;

        return $user;
    }
}

final class UpdatingBranches implements BranchRepository
{
    public function __construct(private Branch $branch) {}

    public function save(Branch $branch): Branch
    {
        return $branch;
    }

    public function find(CompanyId $companyId, int $branchId): ?Branch
    {
        if ($this->branch->belongsTo($companyId) && $this->branch->id() === $branchId) {
            return $this->branch;
        }

        return null;
    }

    public function countActiveByCompanyId(CompanyId $companyId): int
    {
        return 1;
    }

    public function search(SearchBranchesCriteria $criteria): \App\Features\Shared\Domain\PagedResult
    {
        return new \App\Features\Shared\Domain\PagedResult([], 0, $criteria->page, $criteria->perPage);
    }


}

final class UpdatingSchedules implements ScheduleRepository
{
    public function __construct(private Schedule $schedule) {}

    public function save(Schedule $schedule): Schedule
    {
        return $schedule;
    }

    public function find(CompanyId $companyId, int $scheduleId): ?Schedule
    {
        if ($this->schedule->belongsTo($companyId) && $this->schedule->id() === $scheduleId) {
            return $this->schedule;
        }

        return null;
    }

    public function search(SearchSchedulesCriteria $criteria): \App\Features\Shared\Domain\PagedResult
    {
        return new \App\Features\Shared\Domain\PagedResult([], 0, $criteria->page, $criteria->perPage);
    }


}

final class MemoryPhotos implements ProfilePhotoStore
{
    public function put(int $userId, string $binary, string $extension): string
    {
        return 'profile-photos/'.$userId.'/photo.'.$extension;
    }

    public function delete(?string $path): void
    {
    }
}
