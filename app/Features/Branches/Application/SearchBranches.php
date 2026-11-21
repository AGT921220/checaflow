<?php

declare(strict_types=1);

namespace App\Features\Branches\Application;

use App\Features\Branches\Domain\Branch;
use App\Features\Branches\Domain\BranchRepository;
use App\Features\Branches\Domain\SearchBranchesCriteria;
use App\Features\Shared\Domain\PagedResult;
use App\Features\Shared\Domain\PageQuery;

final class SearchBranches
{
    public function __construct(private BranchRepository $branches) {}

    /**
     * @return list<Branch>
     */
    public function handle(SearchBranchesCriteria $criteria): array
    {
        return $this->branches->search($criteria);
    }

    public function page(SearchBranchesCriteria $criteria, PageQuery $page): PagedResult
    {
        return $this->branches->paginate($criteria, $page);
    }
}
