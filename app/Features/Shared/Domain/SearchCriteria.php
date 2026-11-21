<?php

declare(strict_types=1);

namespace App\Features\Shared\Domain;

use App\Features\Shared\Domain\Exceptions\InvalidPageQuery;

/**
 * Base search input for every feature list: sort whitelist, page and per_page.
 * Feature criteria extend this and add their filter properties.
 */
abstract readonly class SearchCriteria
{
    public Sort $sort;

    public int $page;

    public int $perPage;

    /**
     * @param  list<string>  $allowedSortColumns
     */
    public function __construct(
        string $sortColumn,
        string $sortDirection,
        array $allowedSortColumns,
        int $page = 1,
        int $perPage = 15,
        int $maxPerPage = 100,
    ) {
        if ($page < 1) {
            throw new InvalidPageQuery;
        }

        if ($perPage < 1 || $perPage > $maxPerPage) {
            throw new InvalidPageQuery;
        }

        $this->sort = new Sort($sortColumn, $sortDirection, $allowedSortColumns);
        $this->page = $page;
        $this->perPage = $perPage;
    }

    public function offset(): int
    {
        return ($this->page - 1) * $this->perPage;
    }
}
