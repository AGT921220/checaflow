<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Users;

use App\Features\Branches\Application\CreateBranch;
use App\Features\Branches\Domain\Branch;
use App\Features\Branches\Domain\BranchRepository;
use App\Features\Branches\Domain\CreateBranchData;
use App\Features\Branches\Domain\SearchBranchesCriteria;
use App\Features\Shared\Domain\CompanyId;
use App\Features\Users\Application\AuthorizeActor;
use App\Features\Users\Domain\Company;
use App\Features\Users\Domain\CompanyActor;
use App\Features\Users\Domain\Exceptions\ActorCompanyMismatch;
use App\Features\Users\Domain\Exceptions\ActorCompanyRequired;
use App\Features\Users\Domain\Exceptions\PermissionDenied;
use App\Features\Users\Domain\Exceptions\PersonalAccessDenied;
use App\Features\Users\Domain\PermissionName;
use App\Features\Users\Domain\RolePermissions;
use App\Features\Users\Domain\UserRole;
use PHPUnit\Framework\TestCase;

final class AuthorizeActorTest extends TestCase
{
    public function test_each_role_grants_only_its_catalog(): void
    {
        $map = new RolePermissions;
        $expected = [
            UserRole::SuperAdmin->value => [
                PermissionName::CompaniesManage,
                PermissionName::CompanyAdminsManage,
                PermissionName::AttendanceViewCompanyReport,
            ],
            UserRole::CompanyAdmin->value => [
                PermissionName::BranchesManage,
                PermissionName::SchedulesManage,
                PermissionName::UsersManage,
                PermissionName::EmployeesManage,
                PermissionName::AttendanceViewCompanyReport,
                PermissionName::AttendanceAdjustLogs,
                PermissionName::ProfileUpdateOwnPhoto,
            ],
            UserRole::Hr->value => [
                PermissionName::EmployeesManage,
                PermissionName::SchedulesManage,
                PermissionName::AttendanceViewCompanyReport,
                PermissionName::ProfileUpdateOwnPhoto,
            ],
            UserRole::Employee->value => [
                PermissionName::AttendancePunch,
                PermissionName::AttendanceViewOwnHistory,
                PermissionName::ProfileUpdateOwnPhoto,
            ],
        ];

        foreach (UserRole::cases() as $role) {
            foreach (PermissionName::cases() as $permission) {
                $granted = in_array($permission, $expected[$role->value], true);

                $this->assertSame($granted, $map->allows($role, $permission), $role->value.' '.$permission->value);
            }
        }
    }

    public function test_a_company_admin_cannot_manage_companies(): void
    {
        $access = $this->access();

        $this->expectException(PermissionDenied::class);

        $access->handle(
            UserRole::CompanyAdmin,
            new CompanyId(4),
            PermissionName::CompaniesManage,
        );
    }

    public function test_a_super_admin_cannot_manage_branches(): void
    {
        $access = $this->access();

        $this->expectException(PermissionDenied::class);

        $access->handle(
            UserRole::SuperAdmin,
            null,
            PermissionName::BranchesManage,
            new CompanyId(4),
        );
    }

    public function test_an_employee_cannot_punch_for_another_profile(): void
    {
        $companyId = new CompanyId(4);
        $access = $this->access();

        $this->expectException(PersonalAccessDenied::class);

        $access->handle(
            UserRole::Employee,
            $companyId,
            PermissionName::AttendancePunch,
            $companyId,
            10,
            11,
        );
    }

    public function test_a_company_admin_cannot_open_a_branch_in_another_company(): void
    {
        $access = $this->access();

        $this->expectException(ActorCompanyMismatch::class);

        $access->handle(
            UserRole::CompanyAdmin,
            new CompanyId(4),
            PermissionName::BranchesManage,
            new CompanyId(9),
        );
    }

    public function test_a_tenant_actor_requires_a_company(): void
    {
        $this->expectException(ActorCompanyRequired::class);

        new CompanyActor(UserRole::Hr, null);
    }

    public function test_create_branch_accepts_a_company_admin_with_the_permission(): void
    {
        $companyId = new CompanyId(4);
        $branches = new CatalogBranchRepository;
        $companies = new CatalogCompanies(Company::open(
            'Acme',
            'acme-4',
            null,
            2,
            'small_business',
            10,
            2,
            true,
            'UTC',
        )->withId(4));
        $action = new CreateBranch($branches, $companies, $this->access());

        $branch = $action->handle(new CreateBranchData(
            UserRole::CompanyAdmin,
            $companyId,
            $companyId,
            'North',
            null,
            null,
            null,
            false,
        ));

        $this->assertSame('North', $branch->name());
        $this->assertCount(1, $branches->saved);
    }

    private function access(): AuthorizeActor
    {
        return new AuthorizeActor(new RolePermissions);
    }
}

final class CatalogBranchRepository implements BranchRepository
{
    /** @var list<Branch> */
    public array $saved = [];

    public function save(Branch $branch): Branch
    {
        $this->saved[] = $branch;

        return $branch;
    }

    public function find(CompanyId $companyId, int $branchId): ?Branch
    {
        return null;
    }

    public function countActiveByCompanyId(CompanyId $companyId): int
    {
        $total = 0;

        foreach ($this->saved as $branch) {
            if ($branch->belongsTo($companyId) && $branch->isActive()) {
                $total++;
            }
        }

        return $total;
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

final class CatalogCompanies implements \App\Features\Users\Domain\CompanyRepository
{
    public function __construct(private Company $company) {}

    public function find(CompanyId $companyId): ?Company
    {
        if ($this->company->id()->equals($companyId)) {
            return $this->company;
        }

        return null;
    }

    public function slugExists(string $slug): bool
    {
        return false;
    }

    public function save(Company $company): Company
    {
        return $company;
    }
}
