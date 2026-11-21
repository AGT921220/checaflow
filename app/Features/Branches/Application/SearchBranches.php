<?php

declare(strict_types=1);

namespace App\Features\Branches\Application;

use App\Features\Branches\Domain\BranchRepository;
use App\Features\Branches\Domain\SearchBranchesCriteria;
use App\Features\Shared\Domain\PagedResult;

final class SearchBranches
{
    public function __construct(private BranchRepository $branches) {}

    public function handle(SearchBranchesCriteria $criteria): PagedResult
    {
        return $this->branches->search($criteria);
    }
}
