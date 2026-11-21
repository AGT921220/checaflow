<?php

declare(strict_types=1);

namespace App\Features\Branches\Domain;

use App\Features\Shared\Domain\CompanyId;
use App\Features\Shared\Domain\PagedResult;

interface BranchRepository
{
    public function save(Branch $branch): Branch;

    public function find(CompanyId $companyId, int $branchId): ?Branch;

    /**
     * Active branches for one tenant only.
     */
    public function countActiveByCompanyId(CompanyId $companyId): int;

    public function search(SearchBranchesCriteria $criteria): PagedResult;
}
