<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Branches;

use App\Features\Branches\Application\UpdateBranch;
use App\Features\Branches\Domain\Branch;
use App\Features\Branches\Domain\BranchRepository;
use App\Features\Branches\Domain\Exceptions\BranchLimitExceeded;
use App\Features\Branches\Domain\SearchBranchesCriteria;
use App\Features\Branches\Domain\UpdateBranchData;
use App\Features\Shared\Domain\CompanyId;
use App\Features\Users\Application\AuthorizeActor;
use App\Features\Users\Domain\Company;
use App\Features\Users\Domain\CompanyRepository;
use App\Features\Users\Domain\Exceptions\CompanyIsNotPersisted;
use App\Features\Users\Domain\RolePermissions;
use App\Features\Users\Domain\UserRole;
use PHPUnit\Framework\TestCase;

final class UpdateBranchTest extends TestCase
{
    public function test_company_admin_updates_a_branch(): void
    {
        $companyId = new CompanyId(4);
        $branches = new UpdatingBranches;
        $branches->saved[] = Branch::open($companyId, 'Old', null, null, null, false)->withId(7);
        $action = new UpdateBranch($branches, $this->companies($companyId, 2), $this->access());

        $branch = $action->handle(new UpdateBranchData(
            UserRole::CompanyAdmin,
            $companyId,
            $companyId,
            7,
            'North Desk',
            'Street 1',
            null,
            null,
            false,
            true,
        ));

        $this->assertSame('North Desk', $branch->name());
        $this->assertSame('Street 1', $branch->address());
    }

    public function test_reactivating_respects_the_branch_limit(): void
    {
        $companyId = new CompanyId(4);
        $branches = new UpdatingBranches;
        $branches->saved[] = Branch::open($companyId, 'One', null, null, null, false, true)->withId(1);
        $branches->saved[] = Branch::open($companyId, 'Two', null, null, null, false, false)->withId(2);
        $action = new UpdateBranch($branches, $this->companies($companyId, 1), $this->access());

        $this->expectException(BranchLimitExceeded::class);

        $action->handle(new UpdateBranchData(
            UserRole::CompanyAdmin,
            $companyId,
            $companyId,
            2,
            'Two',
            null,
            null,
            null,
            false,
            true,
        ));
    }

    private function companies(CompanyId $companyId, ?int $maxBranches): CompanyRepository
    {
        return new UpdatingCompanies(Company::open(
            'Acme',
            'acme-'.$companyId->value,
            null,
            2,
            'small_business',
            10,
            $maxBranches,
            true,
            'UTC',
        )->withId($companyId->value));
    }

    private function access(): AuthorizeActor
    {
        return new AuthorizeActor(new RolePermissions);
    }
}

final class UpdatingBranches implements BranchRepository
{
    /** @var list<Branch> */
    public array $saved = [];

    public function save(Branch $branch): Branch
    {
        foreach ($this->saved as $index => $row) {
            if ($row->id() === $branch->id()) {
                $this->saved[$index] = $branch;

                return $branch;
            }
        }

        $this->saved[] = $branch;

        return $branch;
    }

    public function find(CompanyId $companyId, int $branchId): ?Branch
    {
        foreach ($this->saved as $branch) {
            if ($branch->belongsTo($companyId) && $branch->id() === $branchId) {
                return $branch;
            }
        }

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

final class UpdatingCompanies implements CompanyRepository
{
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
        return $company;
    }
}
