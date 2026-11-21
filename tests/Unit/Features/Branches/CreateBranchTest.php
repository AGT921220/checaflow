<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Branches;

use App\Features\Branches\Application\CreateBranch;
use App\Features\Branches\Domain\Branch;
use App\Features\Branches\Domain\BranchRepository;
use App\Features\Branches\Domain\CreateBranchData;
use App\Features\Branches\Domain\Exceptions\BranchLimitExceeded;
use App\Features\Branches\Domain\SearchBranchesCriteria;
use App\Features\Shared\Domain\CompanyId;
use App\Features\Users\Application\AuthorizeActor;
use App\Features\Users\Domain\Company;
use App\Features\Users\Domain\CompanyRepository;
use App\Features\Users\Domain\Exceptions\ActorCompanyMismatch;
use App\Features\Users\Domain\Exceptions\CompanyIsNotPersisted;
use App\Features\Users\Domain\Exceptions\InactiveCompanyException;
use App\Features\Users\Domain\Exceptions\PermissionDenied;
use App\Features\Users\Domain\RolePermissions;
use App\Features\Users\Domain\UserRole;
use PHPUnit\Framework\TestCase;

final class CreateBranchTest extends TestCase
{
    public function test_company_admin_opens_a_branch_in_their_company(): void
    {
        $companyId = new CompanyId(4);
        $branches = new MemoryBranchRepository;
        $action = new CreateBranch($branches, $this->companies($companyId, 2), $this->access());

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
        $this->assertTrue($branch->belongsTo($companyId));
        $this->assertCount(1, $branches->saved);
    }

    public function test_it_rejects_when_the_active_branch_limit_is_reached(): void
    {
        $companyId = new CompanyId(4);
        $branches = new MemoryBranchRepository;
        $branches->saved[] = Branch::open($companyId, 'One', null, null, null, false)->withId(1);
        $branches->saved[] = Branch::open($companyId, 'Two', null, null, null, false)->withId(2);
        $action = new CreateBranch($branches, $this->companies($companyId, 2), $this->access());

        $this->expectException(BranchLimitExceeded::class);

        $action->handle(new CreateBranchData(
            UserRole::CompanyAdmin,
            $companyId,
            $companyId,
            'Three',
            null,
            null,
            null,
            false,
        ));
    }

    public function test_unlimited_companies_can_open_more_branches(): void
    {
        $companyId = new CompanyId(4);
        $branches = new MemoryBranchRepository;
        $branches->saved[] = Branch::open($companyId, 'One', null, null, null, false)->withId(1);
        $branches->saved[] = Branch::open($companyId, 'Two', null, null, null, false)->withId(2);
        $action = new CreateBranch($branches, $this->companies($companyId, null), $this->access());

        $branch = $action->handle(new CreateBranchData(
            UserRole::CompanyAdmin,
            $companyId,
            $companyId,
            'Three',
            null,
            null,
            null,
            false,
        ));

        $this->assertSame('Three', $branch->name());
        $this->assertCount(3, $branches->saved);
    }

    public function test_it_rejects_an_inactive_company(): void
    {
        $companyId = new CompanyId(4);
        $action = new CreateBranch(
            new MemoryBranchRepository,
            $this->companies($companyId, 2, false),
            $this->access(),
        );

        $this->expectException(InactiveCompanyException::class);

        $action->handle(new CreateBranchData(
            UserRole::CompanyAdmin,
            $companyId,
            $companyId,
            'North',
            null,
            null,
            null,
            false,
        ));
    }

    public function test_it_rejects_another_company(): void
    {
        $action = new CreateBranch(
            new MemoryBranchRepository,
            $this->companies(new CompanyId(4), 2),
            $this->access(),
        );

        $this->expectException(ActorCompanyMismatch::class);

        $action->handle(new CreateBranchData(
            UserRole::CompanyAdmin,
            new CompanyId(4),
            new CompanyId(9),
            'North',
            null,
            null,
            null,
            false,
        ));
    }

    public function test_a_super_admin_cannot_open_a_branch(): void
    {
        $companyId = new CompanyId(4);
        $action = new CreateBranch(
            new MemoryBranchRepository,
            $this->companies($companyId, 2),
            $this->access(),
        );

        $this->expectException(PermissionDenied::class);

        $action->handle(new CreateBranchData(
            UserRole::SuperAdmin,
            $companyId,
            $companyId,
            'North',
            null,
            null,
            null,
            false,
        ));
    }

    private function companies(CompanyId $companyId, ?int $maxBranches, bool $active = true): CompanyRepository
    {
        return new MemoryCompanies(Company::open(
            'Acme',
            'acme-'.$companyId->value,
            null,
            2,
            'small_business',
            10,
            $maxBranches,
            $active,
            'UTC',
        )->withId($companyId->value));
    }

    private function access(): AuthorizeActor
    {
        return new AuthorizeActor(new RolePermissions);
    }
}

final class MemoryBranchRepository implements BranchRepository
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

    public function search(SearchBranchesCriteria $criteria): \App\Features\Shared\Domain\PagedResult
    {
        return new \App\Features\Shared\Domain\PagedResult([], 0, $criteria->page, $criteria->perPage);
    }


}

final class MemoryCompanies implements CompanyRepository
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
