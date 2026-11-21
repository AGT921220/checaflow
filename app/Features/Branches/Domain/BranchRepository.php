<?php

declare(strict_types=1);

namespace App\Features\Branches\Domain;

use App\Features\Shared\Domain\CompanyId;
use App\Features\Shared\Domain\PagedResult;
use App\Features\Shared\Domain\PageQuery;

interface BranchRepository
{
    public function save(Branch $branch): Branch;

    public function find(CompanyId $companyId, int $branchId): ?Branch;

    /**
     * Active branches for one tenant only.
     */
    public function countActiveByCompanyId(CompanyId $companyId): int;

    /**
     * @return list<Branch>
     */
    public function search(SearchBranchesCriteria $criteria): array;

    public function paginate(SearchBranchesCriteria $criteria, PageQuery $page): PagedResult;
}
