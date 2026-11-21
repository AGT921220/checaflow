<?php

declare(strict_types=1);

namespace App\Features\Shared\Domain;

use App\Features\Shared\Domain\Exceptions\InvalidSortColumn;
use App\Features\Shared\Domain\Exceptions\InvalidSortDirection;

final readonly class Sort
{
    /**
     * @param  list<string>  $allowedColumns
     */
    public function __construct(
        public string $column,
        public string $direction,
        array $allowedColumns,
    ) {
        if (! in_array($column, $allowedColumns, true)) {
            throw new InvalidSortColumn($column);
        }

        if (! in_array($direction, ['asc', 'desc'], true)) {
            throw new InvalidSortDirection($direction);
        }
    }
}
