<?php

declare(strict_types=1);

namespace App\Features\Branches\Application;

use App\Features\Branches\Domain\Branch;
use App\Features\Branches\Domain\BranchRepository;
use App\Features\Branches\Domain\SearchBranchesCriteria;

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
}
