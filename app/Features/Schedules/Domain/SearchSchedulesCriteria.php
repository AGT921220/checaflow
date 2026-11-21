<?php

declare(strict_types=1);

namespace App\Features\Schedules\Domain;

use App\Features\Shared\Domain\CompanyId;
use App\Features\Shared\Domain\Sort;

final readonly class SearchSchedulesCriteria
{
    public Sort $sort;

    public function __construct(
        public CompanyId $companyId,
        public ?bool $isActive = null,
        string $sortColumn = 'name',
        string $sortDirection = 'asc',
    ) {
        $this->sort = new Sort($sortColumn, $sortDirection, ['name', 'is_active']);
    }
}
