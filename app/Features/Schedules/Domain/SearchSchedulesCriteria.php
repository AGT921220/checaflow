<?php

declare(strict_types=1);

namespace App\Features\Schedules\Domain;

use App\Features\Shared\Domain\CompanyId;
use App\Features\Shared\Domain\SearchCriteria;

final readonly class SearchSchedulesCriteria extends SearchCriteria
{
    /**
     * @param  list<string>  $allowedSortColumns
     */
    public function __construct(
        public ?CompanyId $companyId,
        public ?bool $isActive = null,
        string $sortColumn = 'name',
        string $sortDirection = 'asc',
        int $page = 1,
        int $perPage = 15,
        array $allowedSortColumns = ['name', 'is_active'],
        int $maxPerPage = 100,
    ) {
        parent::__construct(
            $sortColumn,
            $sortDirection,
            $allowedSortColumns,
            $page,
            $perPage,
            $maxPerPage,
        );
    }
}
