<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Users;

use App\Features\Branches\Domain\Branch;
use App\Features\Branches\Domain\BranchRepository;
use App\Features\Branches\Domain\SearchBranchesCriteria;
use App\Features\Shared\Domain\CompanyId;
use App\Features\Users\Application\AuthorizeActor;
use App\Features\Users\Application\UpdateCompanyLimits;
use App\Features\Users\Domain\Company;
use App\Features\Users\Domain\CompanyRepository;
use App\Features\Users\Domain\Exceptions\BranchLimitBelowActiveCount;
use App\Features\Users\Domain\Exceptions\CompanyIsNotPersisted;
use App\Features\Users\Domain\Exceptions\PermissionDenied;
use App\Features\Users\Domain\Plan;
use App\Features\Users\Domain\PlanRepository;
use App\Features\Users\Domain\RolePermissions;
use App\Features\Users\Domain\UpdateCompanyLimitsData;
use App\Features\Users\Domain\UserRole;
use PHPUnit\Framework\TestCase;

final class UpdateCompanyLimitsTest extends TestCase
{
    public function test_super_admin_updates_employee_and_branch_limits(): void
    {
        $companyId = new CompanyId(4);
        $companies = new LimitCompanies($this->company($companyId, 2));
        $action = new UpdateCompanyLimits(
            $companies,
            new LimitPlans,
            new LimitBranches,
            new AuthorizeActor(new RolePermissions),
        );

        $updated = $action->handle(new UpdateCompanyLimitsData(
            UserRole::SuperAdmin,
            $companyId,
            20,
            5,
        ));

        $this->assertSame(20, $updated->maxEmployees());
        $this->assertSame(5, $updated->maxBranches());
        $this->assertCount(1, $companies->saved);
    }

    public function test_it_rejects_a_branch_cap_below_active_branches(): void
    {
        $companyId = new CompanyId(4);
        $branches = new LimitBranches;
        $branches->active[$companyId->value] = 3;
        $companies = new LimitCompanies($this->company($companyId, 5));
        $action = new UpdateCompanyLimits(
            $companies,
            new LimitPlans,
            $branches,
            new AuthorizeActor(new RolePermissions),
        );

        try {
            $action->handle(new UpdateCompanyLimitsData(
                UserRole::SuperAdmin,
                $companyId,
                20,
                2,
            ));
            $this->fail('A branch cap below the active count must not be saved.');
        } catch (BranchLimitBelowActiveCount) {
            $this->assertSame([], $companies->saved);
        }
    }

    public function test_null_branch_cap_means_unlimited(): void
    {
        $companyId = new CompanyId(4);
        $branches = new LimitBranches;
        $branches->active[$companyId->value] = 8;
        $companies = new LimitCompanies($this->company($companyId, 2));
        $action = new UpdateCompanyLimits(
            $companies,
            new LimitPlans,
            $branches,
            new AuthorizeActor(new RolePermissions),
        );

        $updated = $action->handle(new UpdateCompanyLimitsData(
            UserRole::SuperAdmin,
            $companyId,
            40,
            null,
        ));

        $this->assertNull($updated->maxBranches());
    }

    public function test_a_company_admin_cannot_update_limits(): void
    {
        $companyId = new CompanyId(4);
        $companies = new LimitCompanies($this->company($companyId, 2));
        $action = new UpdateCompanyLimits(
            $companies,
            new LimitPlans,
            new LimitBranches,
            new AuthorizeActor(new RolePermissions),
        );

        try {
            $action->handle(new UpdateCompanyLimitsData(
                UserRole::CompanyAdmin,
                $companyId,
                20,
                5,
            ));
            $this->fail('A company admin must not update company limits.');
        } catch (PermissionDenied) {
            $this->assertSame([], $companies->saved);
        }
    }

    private function company(CompanyId $companyId, ?int $maxBranches): Company
    {
        return Company::open(
            'Acme',
            'acme-'.$companyId->value,
            null,
            2,
            'small_business',
            10,
            $maxBranches,
            true,
            'UTC',
        )->withId($companyId->value);
    }
}

final class LimitCompanies implements CompanyRepository
{
    /** @var list<Company> */
    public array $saved = [];

    public function __construct(private Company $company) {}

    public function find(CompanyId $companyId): ?Company
    {
        try {
            if ($this->company->id()->equals($companyId)) {
                return $this->company;
            }
        } catch (CompanyIsNotPersisted) {
            return null;
        }

        return null;
    }

    public function slugExists(string $slug): bool
    {
        return false;
    }

    public function save(Company $company): Company
    {
        $this->saved[] = $company;

        return $company;
    }
}

final class LimitBranches implements BranchRepository
{
    /** @var array<int, int> */
    public array $active = [];

    public function save(Branch $branch): Branch
    {
        return $branch;
    }

    public function find(CompanyId $companyId, int $branchId): ?Branch
    {
        return null;
    }

    public function countActiveByCompanyId(CompanyId $companyId): int
    {
        return $this->active[$companyId->value] ?? 0;
    }

    public function search(SearchBranchesCriteria $criteria): array
    {
        return [];
    }
}

final class LimitPlans implements PlanRepository
{
    public function find(int $planId): ?Plan
    {
        return match ($planId) {
            2 => new Plan(2, 'small_business', '10 a 49 empleados (Pyme)', 10, 49, 'monthly_or_annual', 20, null, 200, 'month'),
            default => null,
        };
    }

    public function all(): array
    {
        return array_values(array_filter([$this->find(2)]));
    }
}
